# Parish Management System - Feature Updates Summary

## Overview
This document summarizes all the new features and enhancements added to the Parish Management System.

## Features Implemented

### 1. **Parishioners.php - Enhanced Actions**
- ✅ Added **View** button - Links to family view page
- ✅ Added **Edit** button - Opens parishioner edit form
- ✅ Added **Delete** button - Removes parishioner with confirmation dialog
- All actions include proper confirmation dialogs for destructive operations

### 2. **Family View (family_view.php) - Enhanced Subscription Display**
- ✅ **Rupee Symbol (₹)** - All amounts now display with Indian Rupee symbol
- ✅ **Last Payment Details** - Shows most recent payment with date and amount in green highlight box
- ✅ **Total Paid (All Time)** - Displays cumulative subscription payments in yellow highlight box
- ✅ **Balance Due Calculation** - Automatically calculates based on subscription type:
  - Monthly subscriptions: Shows current month's balance
  - Yearly subscriptions: Shows current year's balance
- ✅ **Payment History** - Scrollable list of all subscription payments
- ✅ **Download QR Code Button** - Generate and download family QR code

### 3. **Family Form (family_form.php) - Image Upload & Subscription Settings**

#### Image Upload Features:
- ✅ **Head of Family Photo**
  - Browse and upload image file
  - 📷 Camera capture button for direct photo capture
  - Preview existing image
  - Supports both file upload and camera capture

- ✅ **Spouse Photo**
  - Browse and upload image file
  - 📷 Camera capture button for direct photo capture
  - Preview existing image
  - Supports both file upload and camera capture

#### Subscription Configuration:
- ✅ **Subscription Type** - Choose between:
  - Yearly (default: ₹1200)
  - Monthly (default: ₹100)
- ✅ **Subscription Amount** - Customizable amount per family
- ✅ **Auto-adjustment** - Amount automatically adjusts when switching between monthly/yearly

### 4. **QR Code Generation (generate_qr.php)**
- ✅ **QR Code Generation** - Creates QR code based on family ID
- ✅ **Family ID Display** - Shows family code below QR code
- ✅ **Family Name Display** - Shows family name below family code
- ✅ **Download Functionality** - Prompts download with proper filename
- ✅ **Storage** - QR codes saved in `/qrcodes/` directory
- Uses Google Charts API for QR generation

### 5. **Families.php - QR Code Scanner**
- ✅ **Scan QR Button** - Opens camera scanner modal
- ✅ **Live Camera Feed** - Real-time QR code scanning
- ✅ **Auto-Search** - Automatically searches for family after successful scan
- ✅ **Modal Interface** - Clean popup interface with close button
- Uses html5-qrcode library for scanning functionality

### 6. **Sidebar Toggle Functionality**
- ✅ **Toggle Button in Sidebar** - Top-right corner of sidebar
- ✅ **Toggle Button in Header** - Left side of top bar
- ✅ **Smooth Animation** - CSS transitions for show/hide
- ✅ **Responsive Layout** - Main content adjusts when sidebar is hidden
- ✅ **Persistent State** - Works across all pages

## Database Changes

### New Columns Added to `families` Table:
- `head_image` (TEXT) - Path to head of family photo
- `spouse_image` (TEXT) - Path to spouse photo
- `subscription_type` (TEXT) - 'monthly' or 'yearly' (default: 'yearly')
- `subscription_amount` (REAL) - Custom subscription amount (default: 1200)

### New Directories Created:
- `/uploads/` - Stores uploaded family member images
- `/qrcodes/` - Stores generated QR code images

## Technical Details

### Image Upload Implementation:
- Supports both file upload and camera capture
- Base64 encoding for camera captures
- Unique filenames using timestamp and uniqid()
- Automatic directory creation if not exists

### QR Code Implementation:
- Google Charts API for QR generation
- GD library for adding text below QR code
- PNG format with white background
- 300x300px QR code with 60px extra space for text

### QR Scanner Implementation:
- html5-qrcode library (v2.3.8)
- Environment-facing camera (rear camera on mobile)
- 10 FPS scanning rate
- 250x250px scan box
- Auto-submit search form on successful scan

### Subscription Calculation Logic:
- **Monthly**: Checks payments in current month (YYYY-MM)
- **Yearly**: Checks payments in current year (YYYY)
- Calculates balance as: `subscription_amount - paid_this_period`
- Displays "Paid ✓" when balance is zero or negative

## Files Modified

1. **db.php** - Database schema updates and migrations
2. **parishioners.php** - Added delete handler and action buttons
3. **family_view.php** - Enhanced subscription display and QR download button
4. **family_form.php** - Image upload fields and subscription settings
5. **families.php** - QR scanner integration
6. **includes/header.php** - Sidebar toggle button
7. **includes/footer.php** - Toggle JavaScript function
8. **assets/css/style.css** - Smooth transitions for sidebar

## Files Created

1. **generate_qr.php** - QR code generation and download handler

## Usage Instructions

### Uploading Family Photos:
1. Go to Families → Edit Family
2. Use "Browse" button to select image OR click "📷 Capture" to use camera
3. For camera: Click capture once to start camera, click again to take photo
4. Save the form to upload images

### Setting Subscription Type:
1. Go to Families → Edit Family
2. Select "Monthly" or "Yearly" from Subscription Type dropdown
3. Adjust amount if needed (auto-fills default based on type)
4. Save the form

### Downloading QR Code:
1. Go to Family View page
2. Click "📱 Download QR Code" button
3. QR code image will download with family code and name

### Scanning QR Code:
1. Go to Families page
2. Click "📷 Scan QR" button
3. Allow camera access
4. Point camera at QR code
5. Family will be automatically searched and displayed

### Toggling Sidebar:
1. Click "☰" button in sidebar (top-right) OR
2. Click "☰" button in header (top-left)
3. Sidebar will smoothly hide/show

## Browser Compatibility

- **Camera Features**: Requires HTTPS or localhost
- **QR Scanner**: Works on modern browsers (Chrome, Firefox, Safari, Edge)
- **Image Upload**: All modern browsers
- **Sidebar Toggle**: All browsers

## Security Notes

- Image uploads are validated by file extension
- Base64 images are sanitized before decoding
- QR codes use family_code (not sensitive data)
- Delete operations require user confirmation

## Future Enhancements (Suggestions)

- Image compression for uploaded photos
- Crop/rotate functionality for camera captures
- Bulk QR code generation for all families
- Export family data with QR codes to PDF
- Subscription payment reminders
- SMS/WhatsApp integration for QR codes

---

**Last Updated**: January 27, 2026
**Version**: 2.0
