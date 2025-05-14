# WebP Image Optimizer for OpenMage (Magento 1)

A lightweight module for OpenMage / Magento 1 that replaces `.jpg` and `.png` images with `.webp`.

Magento 2 version available at https://github.com/kudja/magento2-webp

## Features

- Supports both HTML and JSON responses (e.g. AJAX, product data, etc.)
- Converts on-demand: new image paths are queued and processed via cron
- Lightweight output rewriting with minimal performance impact
- Compatible with FPC, custom themes and extensions
- JS fallback for browsers without WebP support ([WebP support chart](https://caniuse.com/webp))
- Conversion modes:
    - Local conversion using `cwebp`
    - Tinify (tinypng.com) API

## Installation
 
Composer (recommended)
```bash
composer require kudja/openmage-tinify
```

From git
```bash
composer config repositories.openmage-tinify vcs https://github.com/kudja/openmage-tinify.git
composer require kudja/openmage-tinify
```

Via modman

```bash
modman clone https://github.com/kudja/openmage-tinify.git
```

Manual way:

Download zip and extract `src` folder to the magento root.

### Dependencies

For local conversion you need to install `cwebp` binary.
You can use `apt-get` or `brew` to install it.

Ubuntu/Debian
```bash
sudo apt install webp
```

If you are using [tinify api](https://tinify.com/developers) you need to install `tinify` library.
```bash
composer require tinify/tinify
```

### Configuration

In Magento admin go to `System > Configuration > Kudja > Tinify (WebP)` section.
