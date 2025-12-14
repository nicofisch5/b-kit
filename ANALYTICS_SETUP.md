# Simple Analytics Setup Guide

## Overview
This is a simple, homemade analytics solution that tracks:
- App usage (page loads)
- User location (country, city)
- Device information (mobile/desktop, OS, browser)
- PWA vs web usage

## Setup Instructions

### Option 1: Use Your Own Server (Recommended)

#### Step 1: Setup Backend Server

1. **Create a simple Node.js server** (already created in `analytics-server.js`)

2. **Install dependencies:**
```bash
npm install express cors
```

3. **Run the server:**
```bash
node analytics-server.js
```

4. **Deploy to your server:**
   - Upload `analytics-server.js` to your server
   - Run with PM2 or similar: `pm2 start analytics-server.js`
   - Configure Nginx/Apache to proxy requests

#### Step 2: Configure Frontend

1. **Edit** `/src/utils/analytics.js`:
```javascript
const ANALYTICS_ENDPOINT = 'https://your-server.com/api/track'
```
Change to your actual server URL.

2. **Rebuild app:**
```bash
npm run build
```

### Option 2: Use a Free Service (No Backend Needed)

If you don't want to run your own server, use a free service like:

**Google Sheets API (Free):**

Edit `/src/utils/analytics.js`:
```javascript
async function sendAnalytics(data) {
  const SHEET_URL = 'YOUR_GOOGLE_APPS_SCRIPT_URL'

  await fetch(SHEET_URL, {
    method: 'POST',
    mode: 'no-cors',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  })
}
```

Create Google Apps Script:
```javascript
function doPost(e) {
  var sheet = SpreadsheetApp.getActiveSheet();
  var data = JSON.parse(e.postData.contents);

  sheet.appendRow([
    new Date(),
    data.event,
    data.location.country,
    data.location.city,
    data.device.deviceType,
    data.device.os,
    data.device.browser,
    data.appVersion
  ]);

  return ContentService.createTextOutput('OK');
}
```

### Option 3: File-Only (No Backend)

Use localStorage to save analytics locally and export manually:

Edit `/src/utils/analytics.js`:
```javascript
async function sendAnalytics(data) {
  // Save to localStorage
  const existing = JSON.parse(localStorage.getItem('bstrack_analytics') || '[]')
  existing.push(data)

  // Keep last 100 entries
  if (existing.length > 100) {
    existing.shift()
  }

  localStorage.setItem('bstrack_analytics', JSON.stringify(existing))
}

// Export function to manually get data
export function exportAnalytics() {
  const data = localStorage.getItem('bstrack_analytics')
  console.log(JSON.parse(data))
  return data
}
```

## View Analytics

### Method 1: API Endpoints

**Summary Dashboard:**
```
GET http://your-server.com/api/analytics/summary
```

Returns:
```json
{
  "totalVisits": 1523,
  "countries": {
    "United States": 450,
    "France": 320,
    "Germany": 180,
    "Spain": 150,
    ...
  },
  "devices": {
    "Mobile": 850,
    "Desktop": 600,
    "Tablet": 73
  },
  "browsers": {
    "Chrome": 900,
    "Safari": 400,
    "Firefox": 223
  }
}
```

**Export CSV:**
```
GET http://your-server.com/api/analytics/export
```

Downloads `analytics.csv` file.

### Method 2: View Raw Data

Check the `analytics-data.json` file on your server:
```bash
cat analytics-data.json | jq .
```

## Privacy Considerations

✅ **Privacy-Friendly:**
- No cookies used
- No user identification
- No personal data stored
- IP addresses used only for geolocation
- Country-level tracking only (not specific addresses)
- GDPR compliant (no personal data)

✅ **User Control:**
- Tracks only once per session
- Can be disabled entirely by commenting out `trackAppLoad()`
- No third-party trackers

## Data Collected

```json
{
  "event": "app_load",
  "timestamp": "2025-01-15T10:30:00.000Z",
  "location": {
    "country": "France",
    "countryCode": "FR",
    "city": "Paris",
    "timezone": "Europe/Paris"
  },
  "device": {
    "deviceType": "Mobile",
    "os": "iOS",
    "browser": "Safari",
    "screenSize": "390x844",
    "language": "fr-FR"
  },
  "appVersion": "1.1.0",
  "isPWA": true,
  "referrer": "https://google.com"
}
```

## Disable Analytics

To completely disable analytics:

**Comment out in `/src/main.js`:**
```javascript
// trackAppLoad().catch(err => console.warn('Analytics failed:', err))
```

## Example Usage

View your analytics dashboard:

```bash
# Start server
node analytics-server.js

# View in browser
open http://localhost:3001/api/analytics/summary

# Or use curl
curl http://localhost:3001/api/analytics/summary | jq .
```

## Advanced: Track Custom Events

```javascript
import { trackEvent } from './utils/analytics'

// Track when user exports game
trackEvent('game_exported', {
  format: 'CSV',
  playerCount: 12
})

// Track when user completes a game
trackEvent('game_completed', {
  duration: '48 minutes',
  totalPoints: 85
})
```

## Cost

- **Free IP geolocation**: 1000 requests/day (ipapi.co)
- **Server hosting**: $5-10/month for simple VPS
- **Total cost**: ~$5-10/month

Or use **completely free** with Google Sheets option: $0/month
