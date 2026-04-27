/**
 * Cloudflare Worker Script
 * Provides edge-level security, rate limiting, and request validation
 */

// Configuration
const CONFIG = {
  // Rate limiting (requests per minute)
  RATE_LIMITS: {
    login: { max: 5, window: 60 },
    api: { max: 30, window: 60 },
    general: { max: 120, window: 60 },
  },
  // Blocked patterns (SQL injection, XSS, etc.)
  BLOCKED_PATTERNS: [
    /(\bselect\b|\bunion\b|\binsert\b|\bupdate\b|\bdelete\b|\bdrop\b)\s+(?!from)/i, // SQL injection
    /<script[^>]*>.*?<\/script>/gi, // Script tags
    /javascript:/gi, // JavaScript protocol
    /on\w+\s*=/gi, // Event handlers
  ],
  // IP Whitelist (empty = allow all)
  IP_WHITELIST: [],
  // JWT secret (should match backend)
  JWT_SECRET: 'your-jwt-secret-here',
};

// Main request handler
async function handleRequest(request) {
  const url = new URL(request.url);
  const ip = request.headers.get('cf-connecting-ip');

  // 1. Block malicious requests
  if (await isBlockedRequest(request, url)) {
    return new Response('Forbidden', { status: 403 });
  }

  // 2. Apply IP whitelist (if configured)
  if (CONFIG.IP_WHITELIST.length > 0 && !CONFIG.IP_WHITELIST.includes(ip)) {
    return new Response('Access Denied', { status: 403 });
  }

  // 3. Check rate limiting
  const rateLimitResult = await checkRateLimit(ip, url.pathname);
  if (rateLimitResult.blocked) {
    return new Response('Too Many Requests', {
      status: 429,
      headers: {
        'Retry-After': rateLimitResult.retryAfter.toString(),
        'X-RateLimit-Limit': rateLimitResult.limit.toString(),
        'X-RateLimit-Remaining': '0',
      },
    });
  }

  // 4. Validate authentication (for protected routes)
  if (isProtectedRoute(url.pathname)) {
    const authResult = await validateAuth(request);
    if (!authResult.valid) {
      return new Response('Unauthorized', { status: 401 });
    }
  }

  // 5. Sanitize request headers
  const cleanRequest = sanitizeRequest(request);

  // 6. Add security headers
  const response = await fetch(cleanRequest);
  return addSecurityHeaders(response);
}

/**
 * Check if request contains malicious patterns
 */
async function isBlockedRequest(request, url) {
  const url_string = url.pathname + url.search;

  // Check URL for malicious patterns
  for (const pattern of CONFIG.BLOCKED_PATTERNS) {
    if (pattern.test(url_string)) {
      console.warn(`[SECURITY] Blocked malicious URL: ${url_string}`);
      return true;
    }
  }

  // Check request body (POST/PUT)
  if (['POST', 'PUT', 'PATCH'].includes(request.method)) {
    try {
      const contentType = request.headers.get('content-type') || '';
      if (contentType.includes('application/json')) {
        const body = await request.clone().json();
        if (containsMaliciousContent(body)) {
          console.warn(`[SECURITY] Blocked malicious request body`);
          return true;
        }
      } else if (contentType.includes('application/x-www-form-urlencoded')) {
        const formData = await request.clone().text();
        for (const pattern of CONFIG.BLOCKED_PATTERNS) {
          if (pattern.test(formData)) {
            console.warn(`[SECURITY] Blocked malicious form data`);
            return true;
          }
        }
      }
    } catch (e) {
      console.error('Error analyzing request body:', e);
    }
  }

  return false;
}

/**
 * Recursively check object for malicious content
 */
function containsMaliciousContent(obj) {
  if (typeof obj === 'string') {
    for (const pattern of CONFIG.BLOCKED_PATTERNS) {
      if (pattern.test(obj)) {
        return true;
      }
    }
  } else if (typeof obj === 'object' && obj !== null) {
    for (const value of Object.values(obj)) {
      if (containsMaliciousContent(value)) {
        return true;
      }
    }
  }
  return false;
}

/**
 * Rate limiting using Cloudflare KV storage
 */
async function checkRateLimit(ip, pathname) {
  const limit = getRateLimitConfig(pathname);
  const key = `rate_limit:${ip}:${pathname}`;
  const data = await RATE_LIMIT_KV.get(key);

  let requestCount = 0;
  let resetTime = Date.now();

  if (data) {
    const stored = JSON.parse(data);
    resetTime = stored.resetTime;
    const elapsedSeconds = (Date.now() - resetTime) / 1000;

    if (elapsedSeconds < limit.window) {
      requestCount = stored.count + 1;
    } else {
      requestCount = 1;
      resetTime = Date.now();
    }
  } else {
    requestCount = 1;
    resetTime = Date.now();
  }

  // Store updated count
  await RATE_LIMIT_KV.put(
    key,
    JSON.stringify({ count: requestCount, resetTime }),
    { expirationTtl: limit.window + 60 }
  );

  const blocked = requestCount > limit.max;
  const retryAfter = blocked ? Math.ceil((resetTime + limit.window * 1000 - Date.now()) / 1000) : 0;

  return {
    blocked,
    limit: limit.max,
    remaining: Math.max(0, limit.max - requestCount),
    retryAfter,
  };
}

/**
 * Get rate limit config based on route
 */
function getRateLimitConfig(pathname) {
  if (pathname.includes('/login') || pathname.includes('/auth')) {
    return CONFIG.RATE_LIMITS.login;
  } else if (pathname.includes('/api/')) {
    return CONFIG.RATE_LIMITS.api;
  }
  return CONFIG.RATE_LIMITS.general;
}

/**
 * Check if route requires authentication
 */
function isProtectedRoute(pathname) {
  const protectedPatterns = [
    /^\/admin\//,
    /^\/api\/.*(?<!login)$/,
    /^\/dashboard/,
    /^\/profile/,
  ];
  return protectedPatterns.some(p => p.test(pathname));
}

/**
 * Validate JWT token in Authorization header
 */
async function validateAuth(request) {
  const authHeader = request.headers.get('authorization');

  if (!authHeader || !authHeader.startsWith('Bearer ')) {
    return { valid: false };
  }

  const token = authHeader.substring(7);

  try {
    // Verify JWT (simplified - in production use crypto library)
    const parts = token.split('.');
    if (parts.length !== 3) {
      return { valid: false };
    }

    // Check token expiration
    const payload = JSON.parse(atob(parts[1]));
    if (payload.exp && payload.exp * 1000 < Date.now()) {
      return { valid: false };
    }

    return { valid: true, payload };
  } catch (e) {
    console.error('JWT validation error:', e);
    return { valid: false };
  }
}

/**
 * Sanitize request by removing/escaping suspicious headers
 */
function sanitizeRequest(request) {
  const headers = new Headers(request.headers);

  // Remove suspicious headers
  [
    'x-forwarded-for',
    'x-original-url',
    'x-rewrite-url',
    'x-http-method-override',
  ].forEach(h => headers.delete(h));

  // Add security headers
  headers.set('x-real-ip', request.headers.get('cf-connecting-ip'));
  headers.set('x-cloudflare-origin-ip', request.headers.get('cf-connecting-ip'));

  return new Request(request, { headers });
}

/**
 * Add comprehensive security headers to response
 */
function addSecurityHeaders(response) {
  const headers = new Headers(response.headers);

  // HSTS - Force HTTPS
  headers.set('strict-transport-security', 'max-age=31536000; includeSubDomains; preload');

  // CSP - Content Security Policy
  headers.set(
    'content-security-policy',
    "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self'; frame-ancestors 'none';"
  );

  // X-Frame-Options - Prevent clickjacking
  headers.set('x-frame-options', 'DENY');

  // X-Content-Type-Options - Prevent MIME type sniffing
  headers.set('x-content-type-options', 'nosniff');

  // X-XSS-Protection - Legacy XSS protection
  headers.set('x-xss-protection', '1; mode=block');

  // Referrer-Policy
  headers.set('referrer-policy', 'strict-origin-when-cross-origin');

  // Permissions-Policy (formerly Feature-Policy)
  headers.set('permissions-policy', 'camera=(), microphone=(), geolocation=()');

  // Cache-Control - Set reasonable defaults
  if (!headers.has('cache-control')) {
    if (response.status === 404 || response.status === 500) {
      headers.set('cache-control', 'max-age=60');
    } else if (response.headers.get('content-type')?.includes('text/html')) {
      headers.set('cache-control', 'max-age=3600, must-revalidate');
    } else {
      headers.set('cache-control', 'max-age=86400, public');
    }
  }

  // Remove server identification
  headers.delete('server');
  headers.delete('x-powered-by');

  return new Response(response.body, {
    status: response.status,
    statusText: response.statusText,
    headers,
  });
}

/**
 * Handle requests
 */
addEventListener('fetch', event => {
  event.respondWith(handleRequest(event.request));
});

/**
 * Handle 404 errors
 */
async function handleNotFound(request) {
  return new Response('Not Found', {
    status: 404,
    headers: { 'content-type': 'text/plain' },
  });
}

/**
 * Health check endpoint
 */
async function handleHealthCheck() {
  return new Response(
    JSON.stringify({
      status: 'ok',
      timestamp: new Date().toISOString(),
      uptime: Math.floor(performance.now() / 1000),
    }),
    {
      headers: { 'content-type': 'application/json' },
    }
  );
}

// Export for Wrangler
export default {
  async fetch(request) {
    try {
      // Health check
      if (new URL(request.url).pathname === '/health') {
        return handleHealthCheck();
      }

      return await handleRequest(request);
    } catch (e) {
      console.error('Worker error:', e);
      return new Response('Internal Server Error', { status: 500 });
    }
  },
};
