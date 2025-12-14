/**
 * Netlify Function: Analytics Summary
 * Endpoint: /.netlify/functions/analytics-summary
 */

import { getStore } from '@netlify/blobs'

export default async (req, context) => {
  try {
    // Get Netlify Blobs store
    const store = getStore('analytics')

    // Get all analytics data
    let data = []
    try {
      const storedData = await store.get('visits', { type: 'json' })
      if (storedData) {
        data = storedData
      }
    } catch (err) {
      console.log('No analytics data found')
    }

    // Calculate summary
    const summary = {
      totalVisits: data.length,
      countries: {},
      cities: {},
      devices: {},
      browsers: {},
      os: {},
      pwaUsers: 0,
      webUsers: 0,
      lastVisits: data.slice(-20).reverse(),
      dateRange: {
        first: data[0]?.timestamp || 'N/A',
        last: data[data.length - 1]?.timestamp || 'N/A'
      }
    }

    // Process data
    data.forEach(item => {
      // Countries
      const country = item.location?.country || 'Unknown'
      summary.countries[country] = (summary.countries[country] || 0) + 1

      // Cities
      const city = item.location?.city || 'Unknown'
      summary.cities[city] = (summary.cities[city] || 0) + 1

      // Devices
      const device = item.device?.deviceType || 'Unknown'
      summary.devices[device] = (summary.devices[device] || 0) + 1

      // Browsers
      const browser = item.device?.browser || 'Unknown'
      summary.browsers[browser] = (summary.browsers[browser] || 0) + 1

      // OS
      const os = item.device?.os || 'Unknown'
      summary.os[os] = (summary.os[os] || 0) + 1

      // PWA vs Web
      if (item.isPWA) {
        summary.pwaUsers++
      } else {
        summary.webUsers++
      }
    })

    // Sort countries by count (top 10)
    summary.countries = Object.entries(summary.countries)
      .sort((a, b) => b[1] - a[1])
      .slice(0, 10)
      .reduce((obj, [key, val]) => ({ ...obj, [key]: val }), {})

    // Sort devices
    summary.devices = Object.entries(summary.devices)
      .sort((a, b) => b[1] - a[1])
      .reduce((obj, [key, val]) => ({ ...obj, [key]: val }), {})

    // Sort browsers
    summary.browsers = Object.entries(summary.browsers)
      .sort((a, b) => b[1] - a[1])
      .reduce((obj, [key, val]) => ({ ...obj, [key]: val }), {})

    return new Response(JSON.stringify(summary), {
      status: 200,
      headers: {
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*',
        'Cache-Control': 'public, max-age=300' // Cache for 5 minutes
      }
    })

  } catch (error) {
    console.error('Error generating summary:', error)
    return new Response(JSON.stringify({ error: error.message }), {
      status: 500,
      headers: { 'Content-Type': 'application/json' }
    })
  }
}
