/**
 * Simple Analytics Server (Node.js + Express)
 *
 * SETUP:
 * 1. npm install express cors
 * 2. node analytics-server.js
 * 3. Deploy to your server
 */

const express = require('express')
const cors = require('cors')
const fs = require('fs')
const path = require('path')

const app = express()
const PORT = 3001 // Change this

app.use(cors())
app.use(express.json())

// File to store analytics
const ANALYTICS_FILE = path.join(__dirname, 'analytics-data.json')

// Initialize file if doesn't exist
if (!fs.existsSync(ANALYTICS_FILE)) {
  fs.writeFileSync(ANALYTICS_FILE, JSON.stringify([]))
}

// Endpoint to receive analytics
app.post('/api/track', (req, res) => {
  try {
    const analyticsData = req.body

    // Add server-side data
    analyticsData.serverTimestamp = new Date().toISOString()
    analyticsData.ip = req.ip || req.connection.remoteAddress

    // Read existing data
    let data = []
    try {
      const fileContent = fs.readFileSync(ANALYTICS_FILE, 'utf8')
      data = JSON.parse(fileContent)
    } catch (err) {
      console.error('Error reading file:', err)
    }

    // Add new entry
    data.push(analyticsData)

    // Keep only last 10,000 entries (prevent file from growing too large)
    if (data.length > 10000) {
      data = data.slice(-10000)
    }

    // Save to file
    fs.writeFileSync(ANALYTICS_FILE, JSON.stringify(data, null, 2))

    console.log(`✅ Analytics tracked: ${analyticsData.event} from ${analyticsData.location?.country || 'Unknown'}`)

    res.status(200).json({ success: true })
  } catch (error) {
    console.error('Error tracking analytics:', error)
    res.status(500).json({ success: false, error: error.message })
  }
})

// Endpoint to view analytics summary
app.get('/api/analytics/summary', (req, res) => {
  try {
    const fileContent = fs.readFileSync(ANALYTICS_FILE, 'utf8')
    const data = JSON.parse(fileContent)

    // Calculate summary
    const summary = {
      totalVisits: data.length,
      countries: {},
      devices: {},
      browsers: {},
      os: {},
      lastVisits: data.slice(-10).reverse(),
      dateRange: {
        first: data[0]?.timestamp || 'N/A',
        last: data[data.length - 1]?.timestamp || 'N/A'
      }
    }

    // Count by country
    data.forEach(item => {
      const country = item.location?.country || 'Unknown'
      summary.countries[country] = (summary.countries[country] || 0) + 1

      const device = item.device?.deviceType || 'Unknown'
      summary.devices[device] = (summary.devices[device] || 0) + 1

      const browser = item.device?.browser || 'Unknown'
      summary.browsers[browser] = (summary.browsers[browser] || 0) + 1

      const os = item.device?.os || 'Unknown'
      summary.os[os] = (summary.os[os] || 0) + 1
    })

    // Sort countries by count
    summary.countries = Object.entries(summary.countries)
      .sort((a, b) => b[1] - a[1])
      .reduce((obj, [key, val]) => ({ ...obj, [key]: val }), {})

    res.json(summary)
  } catch (error) {
    console.error('Error generating summary:', error)
    res.status(500).json({ error: error.message })
  }
})

// Endpoint to download raw data (CSV)
app.get('/api/analytics/export', (req, res) => {
  try {
    const fileContent = fs.readFileSync(ANALYTICS_FILE, 'utf8')
    const data = JSON.parse(fileContent)

    // Convert to CSV
    let csv = 'Timestamp,Event,Country,City,Device,OS,Browser,Version,IsPWA\n'
    data.forEach(item => {
      csv += `${item.timestamp},${item.event},${item.location?.country || 'Unknown'},${item.location?.city || 'Unknown'},${item.device?.deviceType || 'Unknown'},${item.device?.os || 'Unknown'},${item.device?.browser || 'Unknown'},${item.appVersion || 'N/A'},${item.isPWA || false}\n`
    })

    res.setHeader('Content-Type', 'text/csv')
    res.setHeader('Content-Disposition', 'attachment; filename=analytics.csv')
    res.send(csv)
  } catch (error) {
    console.error('Error exporting:', error)
    res.status(500).json({ error: error.message })
  }
})

app.listen(PORT, () => {
  console.log(`📊 Analytics server running on http://localhost:${PORT}`)
  console.log(`📈 View summary: http://localhost:${PORT}/api/analytics/summary`)
  console.log(`📥 Export CSV: http://localhost:${PORT}/api/analytics/export`)
})
