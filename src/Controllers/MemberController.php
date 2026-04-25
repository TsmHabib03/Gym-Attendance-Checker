<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Validator;
use App\Core\View;
use App\Services\MemberService;
use InvalidArgumentException;
use Throwable;

final class MemberController
{
    private MemberService $members;

    public function __construct(?MemberService $members = null)
    {
        $this->members = $members ?? new MemberService();
    }

    public function index(): void
    {
        Auth::requireAdmin();

        $search = Request::input('search');
        $list = $this->members->list(is_string($search) ? $search : null);

        View::render('members/index', [
            'members' => $list,
            'csrfToken' => Csrf::token(),
            'search' => is_string($search) ? Validator::string($search, 100) : '',
        ]);
    }

    public function createForm(): void
    {
        Auth::requireAdmin();

        View::render('members/create', [
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function create(): void
    {
        Auth::requireAdmin();
        Csrf::assertValid((string) Request::input('_csrf'));
        $this->rateLimit('member_write', (string) (Auth::id() ?? Request::ip()), 30, 60);

        try {
            $memberId = $this->members->create($_POST, $_FILES);
            Logger::audit('member_create_success', Auth::id(), ['member_id' => $memberId]);
            flash('success', 'Member created successfully.');
            redirect('/members');
        } catch (InvalidArgumentException $throwable) {
            flash('error', $throwable->getMessage());
            $_SESSION['_old'] = [
                'full_name' => (string) ($_POST['full_name'] ?? ''),
                'email' => (string) ($_POST['email'] ?? ''),
                'gender' => (string) ($_POST['gender'] ?? ''),
                'membership_end_date' => (string) ($_POST['membership_end_date'] ?? ''),
            ];
            redirect('/members/create');
        } catch (Throwable $throwable) {
            Logger::error('Member create failed', ['error' => $throwable->getMessage()]);
            flash('error', 'Unable to create member. Please try again.');
            redirect('/members/create');
        }
    }

    public function editForm(): void
    {
        Auth::requireAdmin();

        $id = Validator::int(Request::input('id'), 'Member id');
        $member = $this->members->get($id);

        if (!$member) {
            flash('error', 'Member not found.');
            redirect('/members');
        }

        View::render('members/edit', [
            'csrfToken' => Csrf::token(),
            'member' => $member,
        ]);
    }

    public function qrCard(): void
    {
        Auth::requireAdmin();

        $id = Validator::int(Request::input('id'), 'Member id');
        $member = $this->members->get($id);

        if (!$member) {
            flash('error', 'Member not found.');
            redirect('/members');
        }

        $qrPayloadJson = trim((string) ($member['qr_payload'] ?? ''));
        if ($qrPayloadJson === '') {
            $fallbackPayload = [
                'v' => 1,
                'type' => 'gym_member',
                'qr_token' => (string) ($member['qr_token'] ?? ''),
                'member_code' => (string) ($member['member_code'] ?? ''),
                'full_name' => (string) ($member['full_name'] ?? ''),
                'email' => $member['email'] ?? null,
                'gender' => $member['gender'] ?? null,
                'photo_path' => $member['photo_path'] ?? null,
                'membership_end_date' => (string) ($member['membership_end_date'] ?? ''),
                'generated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ];

            $fallbackJson = json_encode($fallbackPayload, JSON_UNESCAPED_SLASHES);
            $qrPayloadJson = is_string($fallbackJson)
                ? $fallbackJson
                : (string) ($member['qr_token'] ?? '');
        }

        View::render('members/qr', [
            'member' => $member,
            'qrPayloadJson' => $qrPayloadJson,
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function qrBulk(): void
    {
        Auth::requireAdmin();

        $members = $this->members->list();

        View::render('members/qr_bulk', [
            'members' => $members,
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function update(): void
    {
        Auth::requireAdmin();
        Csrf::assertValid((string) Request::input('_csrf'));
        $this->rateLimit('member_write', (string) (Auth::id() ?? Request::ip()), 30, 60);

        try {
            $id = Validator::int(Request::input('id'), 'Member id');
            $this->members->update($id, $_POST, $_FILES);
            Logger::audit('member_update_success', Auth::id(), ['member_id' => $id]);
            flash('success', 'Member updated successfully.');
            redirect('/members');
        } catch (InvalidArgumentException $throwable) {
            flash('error', $throwable->getMessage());
            redirect('/members');
        } catch (Throwable $throwable) {
            Logger::error('Member update failed', ['error' => $throwable->getMessage()]);
            flash('error', 'Unable to update member. Please try again.');
            redirect('/members');
        }
    }

    public function delete(): void
    {
        Auth::requireAdmin();
        Csrf::assertValid((string) Request::input('_csrf'));
        $this->rateLimit('member_delete', (string) (Auth::id() ?? Request::ip()), 10, 60);

        try {
            $id = Validator::int(Request::input('id'), 'Member id');
            $this->members->delete($id);
            Logger::audit('member_delete_success', Auth::id(), ['member_id' => $id]);
            flash('success', 'Member deleted successfully.');
        } catch (InvalidArgumentException $throwable) {
            flash('error', $throwable->getMessage());
        } catch (Throwable $throwable) {
            Logger::error('Member delete failed', ['error' => $throwable->getMessage()]);
            flash('error', 'Unable to delete member. Please try again.');
        }

        redirect('/members');
    }

    public function regenerateQr(): void
    {
        Auth::requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        try {
            Csrf::assertValid((string) Request::input('_csrf'));

            $this->rateLimit(
                'qr_regenerate',
                (string) (Auth::id() ?? Request::ip()),
                10,
                300
            );

            $id = Validator::int(Request::input('id'), 'Member id');
            $member = $this->members->regenerateQr($id);

            Logger::audit('member_qr_regenerated', Auth::id(), [
                'member_id' => $id,
                'member_code' => (string) ($member['member_code'] ?? ''),
            ]);

            $response = [
                'ok' => true,
                'message' => 'QR regenerated successfully.',
                'member' => [
                    'id' => (int) ($member['id'] ?? 0),
                    'member_code' => (string) ($member['member_code'] ?? ''),
                    'qr_token' => (string) ($member['qr_token'] ?? ''),
                    'qr_payload' => (string) ($member['qr_payload'] ?? ''),
                    'updated_at' => (string) ($member['updated_at'] ?? ''),
                ],
            ];

            echo (string) json_encode($response, JSON_UNESCAPED_SLASHES);
        } catch (InvalidArgumentException $throwable) {
            http_response_code(422);
            echo (string) json_encode([
                'ok' => false,
                'message' => $throwable->getMessage(),
            ], JSON_UNESCAPED_SLASHES);
        } catch (Throwable $throwable) {
            Logger::error('regenerateQr failed', ['error' => $throwable->getMessage()]);
            http_response_code(500);
            echo (string) json_encode([
                'ok' => false,
                'message' => 'Server error. Please try again.',
            ], JSON_UNESCAPED_SLASHES);
        }
    }

    /**
     * Apply a rate limit. For QR endpoints we emit JSON 429; for HTML POSTs we
     * flash and redirect to /members.
     */
    private function rateLimit(string $action, string $key, int $maxAttempts, int $windowSeconds): void
    {
        $rate = RateLimiter::hit($action, $key, $maxAttempts, $windowSeconds);
        if (!$rate['allowed']) {
            Logger::audit('rate_limited', Auth::id(), [
                'action' => $action,
                'retry_after' => (int) ($rate['retry_after'] ?? 0),
            ]);

            if (str_starts_with($action, 'qr_')) {
                http_response_code(429);
                header('Content-Type: application/json; charset=utf-8');
                echo (string) json_encode([
                    'ok' => false,
                    'message' => 'Rate limit exceeded. Retry in '
                        . (int) ($rate['retry_after'] ?? 0) . ' seconds.',
                ], JSON_UNESCAPED_SLASHES);
                exit;
            }

            flash('error', 'You are making changes too quickly. Please slow down.');
            redirect('/members');
        }
    }
}
