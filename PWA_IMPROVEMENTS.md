# PWA Improvement Recommendations for B-Strack

> **IMPORTANT - DOCUMENTATION MAINTENANCE:** When implementing these recommendations, update SETUP_GUIDE.md, Basketball_Stats_Tracker_Requirements.md, and ONBOARDING.html accordingly.

## Overview

This document outlines recommendations to enhance B-Strack's Progressive Web App capabilities, focusing on user experience, offline functionality, and engagement.

---

## High Priority Improvements

### 1. Version Update Notifications ✅ IMPLEMENTED

**Status:** Implemented in this update

**What:** Notify users when a new version of the app is available and prompt them to update.

**Why:** Currently using `autoUpdate`, which updates silently. Users should know when updates are available and have control over when to refresh.

**Implementation:**
- Modified PWA registration to use `prompt` mode
- Created `UpdateNotification.vue` component
- Shows a notification banner when update is available
- Users can click to reload and get the latest version
- Non-intrusive UI that doesn't block usage

**Files Modified:**
- `vite.config.js` - Changed registerType to 'prompt'
- `src/components/UpdateNotification.vue` - New component
- `src/App.vue` - Integrated update notification component
- `src/main.js` - Added update detection logic

---

### 2. Offline Indicator

**Status:** Recommended for implementation

**What:** Show a visual indicator when the app is offline vs online.

**Why:** Users should know their connection status, especially since stats are saved locally.

**Implementation:**
```javascript
// In App.vue or a new OfflineIndicator.vue component
const isOnline = ref(navigator.onLine)

window.addEventListener('online', () => isOnline.value = true)
window.addEventListener('offline', () => isOnline.value = false)
```

**UI Suggestion:**
- Small indicator in header or footer
- Green dot = online, red dot = offline
- Optional: "You're offline - data will sync when back online"

---

### 3. App Shortcuts

**Status:** Recommended for implementation

**What:** Quick actions from the home screen icon (long-press on mobile)

**Why:** Faster access to common tasks like starting a new game or viewing stats.

**Implementation:**
Add to `vite.config.js` manifest:
```javascript
shortcuts: [
  {
    name: "New Game",
    short_name: "New",
    description: "Start a new basketball game",
    url: "/?action=new",
    icons: [{ src: "/icon-192x192.png", sizes: "192x192" }]
  },
  {
    name: "View Stats",
    short_name: "Stats",
    description: "View current game statistics",
    url: "/?action=stats",
    icons: [{ src: "/icon-192x192.png", sizes: "192x192" }]
  }
]
```

Then handle the URL parameters in App.vue to trigger appropriate actions.

---

### 4. Share Game Results

**Status:** Recommended for implementation

**What:** Use Web Share API to share game statistics via native share sheet.

**Why:** Makes it easy to share results with team members, parents, or on social media.

**Implementation:**
```javascript
async function shareGameResults() {
  if (navigator.share) {
    try {
      await navigator.share({
        title: 'B-Strack Game Results',
        text: `Final Score: Home ${homeScore} - Opposition ${oppScore}`,
        url: window.location.href
      })
    } catch (err) {
      console.log('Error sharing:', err)
    }
  } else {
    // Fallback: copy to clipboard or download JSON
  }
}
```

Add a "Share" button to the ActionBar or BoxScore modal.

---

## Medium Priority Improvements

### 5. Install Prompt Customization

**Status:** Recommended for implementation

**What:** Custom UI for PWA installation instead of browser default.

**Why:** Better control over when and how users are prompted to install.

**Implementation:**
```javascript
let deferredPrompt = null

window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault()
  deferredPrompt = e
  // Show custom install button
  showInstallButton.value = true
})

async function installApp() {
  if (deferredPrompt) {
    deferredPrompt.prompt()
    const { outcome } = await deferredPrompt.userChoice
    console.log(`User response: ${outcome}`)
    deferredPrompt = null
    showInstallButton.value = false
  }
}
```

**UI Suggestion:**
- Show install banner after user has used app for a few minutes
- "Install B-Strack for offline access and faster performance"
- Dismissible but reappears after a week if not installed

---

### 6. Better Caching Strategy

**Status:** Recommended for implementation

**What:** Optimize caching for different asset types.

**Why:** Faster load times and better offline experience.

**Current:** Basic cache-first strategy for Google Fonts

**Recommended Enhancement:**
```javascript
// In vite.config.js workbox configuration
runtimeCaching: [
  // Google Fonts
  {
    urlPattern: /^https:\/\/fonts\.googleapis\.com\/.*/i,
    handler: 'CacheFirst',
    options: {
      cacheName: 'google-fonts-cache',
      expiration: {
        maxEntries: 10,
        maxAgeSeconds: 60 * 60 * 24 * 365
      }
    }
  },
  // API calls (if added in future)
  {
    urlPattern: /^https:\/\/api\..*\.com\/.*/i,
    handler: 'NetworkFirst',
    options: {
      cacheName: 'api-cache',
      networkTimeoutSeconds: 10,
      expiration: {
        maxEntries: 50,
        maxAgeSeconds: 60 * 60 // 1 hour
      }
    }
  },
  // Images
  {
    urlPattern: /\.(?:png|jpg|jpeg|svg|gif|webp)$/,
    handler: 'CacheFirst',
    options: {
      cacheName: 'images-cache',
      expiration: {
        maxEntries: 50,
        maxAgeSeconds: 60 * 60 * 24 * 30 // 30 days
      }
    }
  }
]
```

---

### 7. Background Sync for Future Cloud Features

**Status:** Future consideration

**What:** Sync data in the background when connection is restored.

**Why:** If cloud storage is added, ensure data doesn't get lost during offline periods.

**Note:** Currently not needed since app is fully local, but valuable if Netlify Blob storage is implemented.

---

### 8. Periodic Background Sync

**Status:** Future consideration

**What:** Periodically sync data or check for updates even when app is closed.

**Why:** Keep app data fresh and up-to-date.

**Use Case:** If implementing features like:
- Team rosters from a backend
- Schedule updates
- League statistics

---

## Low Priority / Nice-to-Have

### 9. Push Notifications

**Status:** Future consideration

**What:** Send notifications for game reminders, score updates, etc.

**Why:** Engagement and timely updates.

**Note:** Requires backend service and user permission. Consider only if:
- Multiple teams use the app
- Central administrator manages schedules
- Users want game reminders

---

### 10. Screenshots for App Store Listing

**Status:** Recommended for implementation

**What:** Add screenshots to manifest for better app store presentation.

**Implementation:**
```javascript
// In manifest
screenshots: [
  {
    src: "/screenshots/home.png",
    sizes: "1280x720",
    type: "image/png",
    label: "Home screen with player roster"
  },
  {
    src: "/screenshots/stats.png",
    sizes: "1280x720",
    type: "image/png",
    label: "Statistics control panel"
  }
]
```

**Steps:**
1. Take screenshots of key app screens
2. Save in `public/screenshots/` folder
3. Add to manifest
4. Update on next build

---

### 11. App Categories and IARC Rating

**Status:** Optional

**What:** Add app categories and ratings to manifest.

**Implementation:**
```javascript
// In manifest
categories: ["sports", "utilities"],
iarc_rating_id: "e84b072d-71b3-4d3e-86ae-31a8ce4e53b7"
```

**Note:** IARC rating ID requires registration at https://www.globalratings.com/

---

### 12. Related Applications

**Status:** Optional

**What:** Link to related apps (iOS app if created, Android app, etc.)

**Implementation:**
```javascript
// In manifest
related_applications: [
  {
    platform: "play",
    url: "https://play.google.com/store/apps/details?id=com.example.bstrack",
    id: "com.example.bstrack"
  }
]
```

---

## Performance Optimizations

### 13. Lazy Loading Components

**Status:** Recommended for implementation

**What:** Load modal components only when needed.

**Why:** Faster initial load time.

**Implementation:**
```javascript
// In App.vue
const BoxScore = defineAsyncComponent(() =>
  import('./components/BoxScore.vue')
)
const PlayerSelectionModal = defineAsyncComponent(() =>
  import('./components/PlayerSelectionModal.vue')
)
```

---

### 14. Service Worker Skip Waiting Prompt

**Status:** ✅ IMPLEMENTED

**What:** Better control over service worker updates.

**Why:** Prevents forced refreshes, gives users control.

**Implementation:** Included in version notification feature.

---

## Security Improvements

### 15. Content Security Policy

**Status:** Recommended for implementation

**What:** Add CSP headers to prevent XSS attacks.

**Implementation:**
Add to `index.html`:
```html
<meta http-equiv="Content-Security-Policy"
      content="default-src 'self';
               script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com;
               style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
               font-src 'self' https://fonts.gstatic.com;
               img-src 'self' data: https:">
```

**Note:** Adjust based on actual requirements. Test thoroughly.

---

### 16. HTTPS Enforcement

**Status:** Already handled by most hosting platforms

**What:** Ensure all traffic uses HTTPS.

**Why:** Required for PWA features like service workers.

**Implementation:** Configure on hosting platform (Netlify, Vercel, etc.)

---

## Analytics and Monitoring

### 17. PWA Analytics

**Status:** Optional

**What:** Track PWA installation, usage patterns, offline usage.

**Implementation Options:**
- Google Analytics 4 with PWA events
- Plausible Analytics (privacy-focused)
- Custom logging

**Events to Track:**
- App installations
- Offline usage sessions
- Update prompts shown/accepted
- Share button usage
- Export frequency

---

### 18. Error Monitoring

**Status:** Recommended for implementation

**What:** Track errors and crashes.

**Implementation:**
```javascript
// In main.js
window.addEventListener('error', (event) => {
  console.error('Global error:', event.error)
  // Send to logging service
})

window.addEventListener('unhandledrejection', (event) => {
  console.error('Unhandled promise rejection:', event.reason)
  // Send to logging service
})
```

**Services:**
- Sentry
- LogRocket
- Custom backend logging

---

## Implementation Priority

### Immediate (This Update)
- ✅ Version update notifications

### Next Sprint
1. Offline indicator
2. Share game results (Web Share API)
3. Lazy loading components
4. Content Security Policy

### Future Enhancements
1. App shortcuts
2. Custom install prompt
3. Better caching strategies
4. Screenshots for manifest
5. Background sync (if cloud storage added)

### Optional / As Needed
- Push notifications (requires use case)
- PWA analytics
- Error monitoring
- Related applications

---

## Testing Recommendations

### PWA Testing Checklist
- [ ] Test offline functionality (airplane mode)
- [ ] Test update notification flow
- [ ] Test install prompt on various devices
- [ ] Verify service worker registration
- [ ] Test cache invalidation
- [ ] Verify manifest.json loads correctly
- [ ] Test on iOS Safari, Android Chrome, Desktop browsers
- [ ] Run Lighthouse PWA audit (target score: 90+)
- [ ] Test install/uninstall process
- [ ] Verify app works in standalone mode

### Tools
- Chrome DevTools → Application tab
- Lighthouse (Chrome DevTools → Lighthouse)
- https://web.dev/measure/
- BrowserStack for cross-device testing

---

## Resources

- [PWA Best Practices](https://web.dev/progressive-web-apps/)
- [Workbox Documentation](https://developers.google.com/web/tools/workbox)
- [Web App Manifest Spec](https://www.w3.org/TR/appmanifest/)
- [Service Worker API](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API)
- [Vite PWA Plugin Docs](https://vite-pwa-org.netlify.app/)

---

**Document Status:** Active - Keep Updated
**Last Updated:** 2025-12-13
**Version:** 1.0
