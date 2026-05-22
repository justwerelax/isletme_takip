# Tailwind CSS Setup Instructions

## Prerequisites

Before setting up Tailwind CSS, you need to have Node.js and npm installed on your system.

### Installing Node.js

1. **Download Node.js:**
   - Visit https://nodejs.org/
   - Download the LTS (Long Term Support) version
   - Run the installer and follow the installation wizard

2. **Verify Installation:**
   ```bash
   node --version
   npm --version
   ```
   You should see version numbers for both commands.

## Setup Steps

### 1. Install Dependencies

Navigate to the project root directory and install Tailwind CSS:

```bash
cd alt-firma-takip
npm install
```

This will install Tailwind CSS as specified in `package.json`.

### 2. Build Tailwind CSS

You have three options for building Tailwind CSS:

#### Option A: One-time Build (Development)
```bash
npm run build:css
```

This compiles `frontend/css/app.css` to `frontend/css/output.css` once.

#### Option B: Watch Mode (Development)
```bash
npm run watch:css
```

This watches for changes in your HTML and CSS files and automatically recompiles when changes are detected. Keep this running in a terminal while developing.

#### Option C: Production Build (Minified)
```bash
npm run build:css:prod
```

This creates a minified version of the CSS file for production deployment.

### 3. Verify Setup

After building, you should see a new file created:
- `frontend/css/output.css`

This is the compiled Tailwind CSS file that should be included in your HTML files.

## Project Files

The following files have been created for Tailwind CSS setup:

1. **package.json** - Contains npm scripts and Tailwind CSS dependency
2. **tailwind.config.js** - Tailwind CSS configuration file
3. **frontend/css/app.css** - Source CSS file with Tailwind directives and custom styles

## Custom Styles

The `frontend/css/app.css` file includes:

- Tailwind base, components, and utilities
- Custom button styles (btn-primary, btn-secondary, btn-danger, btn-success)
- Card component styles
- Input field styles
- Badge styles (success, danger, warning, gray)
- Loading spinner animation
- Modal overlay styles
- Toast notification styles
- Responsive table wrapper
- Balance display colors

## Usage in HTML

To use Tailwind CSS in your HTML files, include the compiled CSS:

```html
<link rel="stylesheet" href="css/output.css">
```

## Development Workflow

1. Start the watch mode: `npm run watch:css`
2. Edit your HTML files in `frontend/pages/`
3. Edit custom styles in `frontend/css/app.css`
4. Tailwind will automatically recompile when you save changes
5. Refresh your browser to see the changes

## Production Deployment

Before deploying to production:

1. Run the production build: `npm run build:css:prod`
2. Upload the `frontend/css/output.css` file to your server
3. Ensure your HTML files reference this CSS file

## Troubleshooting

### npm command not found
- Make sure Node.js is installed correctly
- Restart your terminal after installing Node.js
- Check if Node.js is in your system PATH

### Tailwind not compiling
- Make sure you're in the `alt-firma-takip` directory
- Check that `package.json` and `tailwind.config.js` exist
- Try deleting `node_modules` folder and running `npm install` again

### Changes not reflecting
- Make sure you're running `npm run watch:css` in watch mode
- Check that your HTML files are in the paths specified in `tailwind.config.js`
- Clear your browser cache

## Next Steps

After setting up Tailwind CSS:

1. Continue with Task 1.4: Setup Capacitor for mobile
2. Proceed with database setup (Task 2.x)
3. Start building the frontend pages with Tailwind CSS classes

## Resources

- Tailwind CSS Documentation: https://tailwindcss.com/docs
- Tailwind CSS Cheat Sheet: https://nerdcave.com/tailwind-cheat-sheet
- Tailwind CSS Playground: https://play.tailwindcss.com/
