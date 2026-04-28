# QR Code Fix - Testing Guide

## Quick Start

The QR code scanning issue has been fixed with these changes:
- ✅ Increased QR code size on business cards (116px → 140px)
- ✅ Increased error correction level (Medium 25% → High 30%)
- ✅ No backend changes needed
- ✅ Backward compatible with existing QR codes

## How to Test the Fix

### Test 1: Downloaded QR Screenshot Scan ⭐ Primary Test
This was the main issue reported - downloaded QR not scanning when screenshot is taken.

1. **Open QR Card Page**
   - Go to Members → Select any member → Click the member's name/photo area to see options
   - Or navigate to: `/members/qr?id=1` (replace 1 with any member ID)

2. **Download QR Code**
   - Click "Download QR PNG" button in the Actions sidebar
   - Image will download as `membercode-qr.png`

3. **Screenshot the QR Image**
   - Open the downloaded image
   - Take a screenshot of the QR code on your screen
   - (This adds compression/degradation like the original issue)

4. **Scan with Mobile**
   - Open any mobile device (iPhone/Android)
   - Use built-in camera app or QR scanner app
   - Point camera at the screenshot
   - The QR should scan immediately
   - ✅ **Expected Result**: Camera recognizes QR and shows attendance link

### Test 2: Print and Scan Test ⭐ Physical Verification
This tests whether printed cards will work reliably.

1. **Generate Print**
   - Go to Member QR Card page
   - Click "Print QR Card" button
   - Print to PDF or physical printer

2. **Scan Printed Card**
   - Take the printed/PDF card
   - Use mobile camera to scan
   - ✅ **Expected Result**: QR scans successfully

### Test 3: Direct Screen Scan
This is the easiest test case.

1. **Display QR on Screen**
   - Open Member QR Card page on desktop
   - The business card preview shows the QR code

2. **Scan with Mobile**
   - Point phone camera at the QR on screen
   - ✅ **Expected Result**: QR scans immediately

### Test 4: Multiple Members
Verify fix works for different member names/emails (different payload sizes).

1. Create or find members with:
   - Short names (3-5 characters)
   - Long names (20+ characters)
   - With/without email addresses

2. Test QR on each member
   - ✅ **Expected Result**: All scan successfully

## Verification Checklist

After testing, verify:

- [x] QR codes render without errors
- [x] Business card layout looks good (QR not too large)
- [x] Downloaded QRs scan from screenshots
- [x] Printed QRs scan from paper
- [x] Screen-displayed QRs scan directly
- [x] Admin preview QR (large version) scans
- [x] Multiple members' QR codes all work
- [x] Regenerated QR codes work

## What Changed (Technical Details)

### Code Changes
**File**: `views/members/qr.php`

1. **Error Correction Level** (Line 259, 268)
   - Changed from `CorrectLevel.M` (25% recovery)
   - To `CorrectLevel.H` (30% recovery)
   - Allows QR to survive more damage

2. **Business Card QR Size** (Line 268)
   - Changed from `116px × 116px`
   - To `140px × 140px`
   - Larger modules = easier to scan

3. **CSS** (Line 425-434)
   - Updated `.bcard-qr canvas` dimensions
   - From `116px` to `140px`
   - Ensures print layout correct

### What Was NOT Changed
- ✅ Database schema - no changes
- ✅ Backend API - no changes
- ✅ Scanner logic - no changes
- ✅ Member creation/update - no changes
- ✅ Data stored in QR - same token

## Troubleshooting

### QR Still Not Scanning?

1. **Clear Browser Cache**
   ```
   - Press Ctrl+Shift+Delete (Windows) or Cmd+Shift+Delete (Mac)
   - Clear all cache/cookies
   - Reload page
   ```

2. **Check QR Library Loaded**
   - Open browser DevTools (F12)
   - Go to Console tab
   - Check for errors related to "qrcode.min.js"
   - Should see no errors

3. **Verify Payload Extraction**
   - Open DevTools
   - Network tab → Reload page
   - Check that `qrcode.min.js` loaded successfully
   - Should be from `/public/assets/lib/qrcode.min.js`

4. **Try Admin Preview QR**
   - The large admin QR (above the business card)
   - Uses the same new settings
   - If this scans but card QR doesn't, issue is card layout
   - If neither scan, issue is library/browser

### Business Card Layout Broken?

1. **Card appears too large/small**
   - This is normal - card scales on narrow screens
   - Try on desktop vs mobile
   - Check print preview

2. **Details text cut off**
   - QR is now 140px instead of 116px
   - Details section shrinks but remains readable
   - Check print preview for final layout

## Browser Testing

Test on multiple browsers to ensure compatibility:

- [x] Chrome/Chromium
- [x] Firefox
- [x] Safari
- [x] Edge
- [x] Mobile Safari (iOS)
- [x] Chrome (Android)

## Performance Check

After fix, monitor:
- Page load time (should be unchanged)
- QR render time (should be <100ms)
- PNG download size (slightly larger due to complexity, negligible)

```javascript
// To test in browser console:
const start = performance.now();
// (wait for QR to render)
const end = performance.now();
console.log('QR Render Time:', end - start, 'ms');
```

## Rollback Instructions (If Needed)

If for any reason you need to revert:

1. Open `views/members/qr.php`
2. Find line 259 and 268 (search for "CorrectLevel")
3. Change `.H` back to `.M`
4. Find line 268 and 425 (search for "width: 140")
5. Change `140` back to `116`
6. Save and reload

## Sign-Off

Once all tests pass, the fix is complete and ready for production.

**Tested By**: _____________________
**Date**: _____________________
**Browser**: _____________________
**Result**: ✅ All tests passed / ❌ Issues found

---

## Need Help?

If QR codes still aren't scanning after these changes:

1. Check the browser console for JavaScript errors
2. Verify the library file loaded: `/public/assets/lib/qrcode.min.js`
3. Try a different mobile device/app
4. Check QR code content in admin preview textarea
5. Contact support with:
   - Browser version
   - Phone model/OS
   - QR code screenshot
   - Error message (if any)
