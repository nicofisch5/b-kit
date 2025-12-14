/**
 * Simple Analytics Tracker
 * Tracks app usage and user location (country)
 */

// Netlify Functions endpoint - works automatically in production and dev
const ANALYTICS_ENDPOINT = '/.netlify/functions/track'
const STORAGE_KEY = 'bstrack_analytics_sent'

/**
 * Get user's country using IP geolocation (free service)
 */
async function getUserCountry() {
  try {
    // Using ipapi.co - free service (1000 requests/day)
    const response = await fetch('https://ipapi.co/json/')
    const data = await response.json()
    return {
      country: data.country_name || 'Unknown',
      countryCode: data.country_code || 'XX',
      city: data.city || 'Unknown',
      timezone: data.timezone || 'Unknown'
    }
  } catch (error) {
    console.warn('Could not fetch location:', error)
    return {
      country: 'Unknown',
      countryCode: 'XX',
      city: 'Unknown',
      timezone: 'Unknown'
    }
  }
}

/**
 * Get basic device info
 */
function getDeviceInfo() {
  const ua = navigator.userAgent
  let deviceType = 'Desktop'

  if (/Mobile|Android|iPhone|iPad|iPod/i.test(ua)) {
    deviceType = /iPad|Tablet/i.test(ua) ? 'Tablet' : 'Mobile'
  }

  let os = 'Unknown'
  if (/Windows/i.test(ua)) os = 'Windows'
  else if (/Mac/i.test(ua)) os = 'macOS'
  else if (/Linux/i.test(ua)) os = 'Linux'
  else if (/Android/i.test(ua)) os = 'Android'
  else if (/iOS|iPhone|iPad|iPod/i.test(ua)) os = 'iOS'

  let browser = 'Unknown'
  if (/Chrome/i.test(ua) && !/Edge/i.test(ua)) browser = 'Chrome'
  else if (/Safari/i.test(ua) && !/Chrome/i.test(ua)) browser = 'Safari'
  else if (/Firefox/i.test(ua)) browser = 'Firefox'
  else if (/Edge/i.test(ua)) browser = 'Edge'

  return {
    deviceType,
    os,
    browser,
    screenSize: `${window.screen.width}x${window.screen.height}`,
    language: navigator.language || 'en'
  }
}

/**
 * Send analytics data to your server
 */
async function sendAnalytics(data) {
  try {
    await fetch(ANALYTICS_ENDPOINT, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(data)
    })
    console.log('Analytics sent successfully')
  } catch (error) {
    console.warn('Could not send analytics:', error)
  }
}

/**
 * Track app load (called once per session)
 */
export async function trackAppLoad() {
  // Check if already tracked this session
  const sessionTracked = sessionStorage.getItem(STORAGE_KEY)
  if (sessionTracked) {
    console.log('Analytics already tracked this session')
    return
  }

  // Get location data
  const location = await getUserCountry()
  const device = getDeviceInfo()

  const analyticsData = {
    event: 'app_load',
    timestamp: new Date().toISOString(),
    location,
    device,
    appVersion: '1.1.0', // You can import from package.json
    isPWA: window.matchMedia('(display-mode: standalone)').matches,
    referrer: document.referrer || 'direct'
  }

  // Send to your server
  await sendAnalytics(analyticsData)

  // Mark as tracked for this session
  sessionStorage.setItem(STORAGE_KEY, 'true')
}

/**
 * Track specific events (optional)
 */
export async function trackEvent(eventName, eventData = {}) {
  const location = await getUserCountry()

  const analyticsData = {
    event: eventName,
    timestamp: new Date().toISOString(),
    location,
    data: eventData
  }

  await sendAnalytics(analyticsData)
}

/**
 * Track game completion (when user finishes a game)
 */
export async function trackGameComplete(gameStats) {
  await trackEvent('game_complete', {
    duration: gameStats.duration,
    totalPoints: gameStats.totalPoints,
    quarters: gameStats.quarters
  })
}
