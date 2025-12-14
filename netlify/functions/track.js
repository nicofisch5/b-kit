/**
 * Netlify Function: Track Analytics
 * Endpoint: /.netlify/functions/track
 */

import { getStore } from '@netlify/blobs'

export default async (req, context) => {
  // Only allow POST requests
  if (req.method !== 'POST') {
    return new Response(JSON.stringify({ error: 'Method not allowed' }), {
      status: 405,
      headers: { 'Content-Type': 'application/json' }
    })
  }

  try {
    // Parse request body
    const analyticsData = await req.json()

    // Add server-side data
    analyticsData.serverTimestamp = new Date().toISOString()
    analyticsData.netlifyContext = {
      deploy: context.deploy?.id || 'unknown',
      site: context.site?.id || 'unknown'
    }

    // Get Netlify Blobs store
    const store = getStore('analytics')

    // Get existing analytics data
    let allData = []
    try {
      const existingData = await store.get('visits', { type: 'json' })
      if (existingData) {
        allData = existingData
      }
    } catch (err) {
      console.log('No existing data, starting fresh')
    }

    // Add new entry
    allData.push(analyticsData)

    // Keep only last 10,000 entries to prevent storage bloat
    if (allData.length > 10000) {
      allData = allData.slice(-10000)
    }

    // Store updated data
    await store.setJSON('visits', allData)

    console.log(`✅ Analytics tracked: ${analyticsData.event} from ${analyticsData.location?.country || 'Unknown'}`)

    return new Response(JSON.stringify({ success: true, total: allData.length }), {
      status: 200,
      headers: {
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*'
      }
    })

  } catch (error) {
    console.error('Error tracking analytics:', error)
    return new Response(JSON.stringify({ success: false, error: error.message }), {
      status: 500,
      headers: { 'Content-Type': 'application/json' }
    })
  }
}

export const config = {
  path: '/api/track'
}
