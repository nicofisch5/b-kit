/**
 * Netlify Function: Export Analytics as CSV
 * Endpoint: /.netlify/functions/analytics-export
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

    // Convert to CSV
    let csv = 'Timestamp,Event,Country,CountryCode,City,Timezone,Device,OS,Browser,ScreenSize,Language,Version,IsPWA,Referrer\n'

    data.forEach(item => {
      const row = [
        item.timestamp || '',
        item.event || '',
        item.location?.country || 'Unknown',
        item.location?.countryCode || 'XX',
        item.location?.city || 'Unknown',
        item.location?.timezone || 'Unknown',
        item.device?.deviceType || 'Unknown',
        item.device?.os || 'Unknown',
        item.device?.browser || 'Unknown',
        item.device?.screenSize || 'Unknown',
        item.device?.language || 'Unknown',
        item.appVersion || 'N/A',
        item.isPWA ? 'Yes' : 'No',
        item.referrer || 'direct'
      ]

      // Escape commas in fields
      const escapedRow = row.map(field => {
        const str = String(field)
        return str.includes(',') ? `"${str}"` : str
      })

      csv += escapedRow.join(',') + '\n'
    })

    return new Response(csv, {
      status: 200,
      headers: {
        'Content-Type': 'text/csv',
        'Content-Disposition': `attachment; filename="b-strack-analytics-${new Date().toISOString().split('T')[0]}.csv"`,
        'Access-Control-Allow-Origin': '*'
      }
    })

  } catch (error) {
    console.error('Error exporting:', error)
    return new Response(JSON.stringify({ error: error.message }), {
      status: 500,
      headers: { 'Content-Type': 'application/json' }
    })
  }
}
