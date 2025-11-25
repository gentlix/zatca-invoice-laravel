# QR Code Image Guide

## QR Code Format

The ZATCA QR codes are generated and saved in the following formats:

### SVG Format (Default)
- **Location**: `storage/app/zatca/qr-codes/{invoice_id}.svg`
- **Can be opened in**: Web browsers, image viewers, design software
- **Advantages**: Vector format, scalable, works without imagick extension

### PNG Format (If imagick is installed)
- **Location**: `storage/app/zatca/qr-codes/{invoice_id}.png`
- **Can be opened in**: Any image viewer
- **Advantages**: Raster format, widely compatible

## Opening QR Code Files

### SVG Files
1. **Double-click** the `.svg` file - it will open in your default browser
2. **Right-click** → Open with → Choose an application (browser, image viewer)
3. **Drag and drop** into a browser window

### Converting SVG to PNG

If you need PNG format, you can:

#### Option 1: Install imagick Extension (Recommended for PNG)

**Windows (XAMPP):**
1. Download imagick DLL from: https://pecl.php.net/package/imagick
2. Copy `php_imagick.dll` to `php/ext/` folder
3. Edit `php.ini` and add: `extension=imagick`
4. Restart Apache/PHP

**Linux:**
```bash
sudo apt-get install php-imagick
sudo systemctl restart php-fpm
```

#### Option 2: Use Online Converter
- Upload SVG to: https://convertio.co/svg-png/
- Or use: https://cloudconvert.com/svg-to-png

#### Option 3: Use ImageMagick Command Line
```bash
magick convert input.svg output.png
```

## Viewing QR Codes

### In Browser
Simply open the SVG file in any modern web browser (Chrome, Firefox, Edge, etc.)

### In Image Viewers
Most image viewers support SVG:
- Windows Photos
- IrfanView (with plugin)
- GIMP
- Inkscape

### Testing QR Code
Scan the QR code with:
- ZATCA mobile app
- Any QR code scanner app
- Camera app (on modern smartphones)

## File Locations

- **QR Codes**: `storage/app/zatca/qr-codes/`
- **Example**: `storage/app/zatca/qr-codes/Invoice_INV-001.svg`

## Troubleshooting

### "File won't open"
- **SVG files**: Open in a web browser
- **Check file exists**: Verify the file path
- **Check permissions**: Ensure file is readable

### "Need PNG format"
- Install imagick extension (see above)
- Or convert SVG to PNG using online tools
- Or use the SVG format (works in most applications)

### "QR code not scanning"
- Ensure QR code is clear and not distorted
- Check that the base64 data is correct
- Verify the TLV encoding is valid

