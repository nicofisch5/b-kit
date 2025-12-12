# Quick Setup Guide

## Step 1: Install Dependencies

Open a terminal in the project directory and run:

```bash
npm install
```

This will install all required dependencies:
- Vue.js 3
- Vite (build tool)
- PWA plugin

## Step 2: Add PWA Icons (Optional)

For a complete PWA experience, add the following icon files to the `public/` directory:

- `icon-192x192.png` (192x192 pixels)
- `icon-512x512.png` (512x512 pixels)
- `favicon.ico` (32x32 pixels)
- `apple-touch-icon.png` (180x180 pixels)

You can use any basketball-related icon or logo. If you don't add these, the app will still work but won't have a custom icon when installed.

### Quick Icon Generation

You can generate icons from any image using online tools:
- https://realfavicongenerator.net/
- https://www.favicon-generator.org/

Or use ImageMagick (if installed):
```bash
# Create a simple placeholder icon (requires ImageMagick)
convert -size 512x512 xc:orange -gravity center \
  -fill white -pointsize 200 -annotate +0+0 "🏀" \
  public/icon-512x512.png

convert public/icon-512x512.png -resize 192x192 public/icon-192x192.png
```

## Step 3: Start Development Server

```bash
npm run dev
```

Open your browser and navigate to `http://localhost:5173`

## Step 4: Test the Application

1. **Record a stat**:
   - Click any stat button (e.g., "2PT Made")
   - Select a player from the modal
   - See the score update automatically

2. **Switch quarters**:
   - Click Q2, Q3, Q4, or + OT
   - Stats are tracked per quarter

3. **Test PWA features** (Chrome/Edge):
   - Open DevTools (F12)
   - Go to Application tab → Service Workers
   - Verify service worker is registered

4. **Test responsive design**:
   - Open DevTools (F12)
   - Click the device toolbar icon
   - Test on different screen sizes

## Step 5: Build for Production

When ready to deploy:

```bash
npm run build
```

This creates optimized files in the `dist/` directory.

To preview the production build locally:

```bash
npm run preview
```

## Deployment Options

### Option 1: Netlify (Recommended)

1. Create account at netlify.com
2. Connect your Git repository or drag & drop the `dist` folder
3. Configure build settings:
   - Build command: `npm run build`
   - Publish directory: `dist`
4. Deploy!

### Option 2: Static File Hosting

Upload the contents of the `dist/` directory to any static file hosting:
- GitHub Pages
- Vercel
- Cloudflare Pages
- AWS S3 + CloudFront
- Firebase Hosting

### Option 3: Self-Hosting

Copy the `dist/` directory to your web server and configure to serve the files with proper MIME types.

## First-Time Usage

1. **Open the app**
2. **Start recording stats** immediately - the app comes pre-configured with 12 players
3. **Data is auto-saved** to localStorage every 30 seconds
4. **Install as PWA** for offline access (look for install prompt in browser)

## Customization Tips

### Change Team Name
Currently displays "Home Team" and "Opposition". To customize:
- Edit `src/store/gameStore.js`
- Modify the `homeTeam` and `oppositionTeam` values in the `loadGame()` function

### Modify Player Names
Edit `src/store/gameStore.js`:
- Find `createDefaultPlayers()` function
- Change player names and jersey numbers as needed

### Adjust Colors
Edit `src/assets/main.css`:
- Modify CSS variables under `:root`
- Change `--primary-color`, `--secondary-color`, etc.

## Common Issues

**Q: Icons not showing**
A: Add icon files to `public/` directory or use placeholder images

**Q: PWA not installing**
A: Ensure you're using HTTPS or localhost, and that icons are present

**Q: Data lost after refresh**
A: Check if localStorage is enabled in browser settings

**Q: Buttons too small on mobile**
A: All buttons are minimum 44x44px. If still too small, adjust in CSS

## Next Steps

1. Customize player names and team names
2. Add your own icons
3. Test on actual mobile devices
4. Deploy to production
5. Install as PWA on your device

## Resources

- Vue.js Documentation: https://vuejs.org/
- Vite Documentation: https://vitejs.dev/
- PWA Guidelines: https://web.dev/progressive-web-apps/

---

Need help? Check the main README.md for detailed documentation.
