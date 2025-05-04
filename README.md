# OpenMage (Magento 1) WebP images converter

- convert images to WebP format and replaces jpg|png images on frontend
- directly replaces jpg|png with webp on frontend
- fallback js for browsers that doesn't support webp https://caniuse.com/webp
- ability to use https://tinify.com/ api to convert images or use local conversion via `cwebp`

## Installation

### Prerequisites

To use local conversion, you need to install `cwebp` binary. You can find it in the [webp](https://developers.google.com/speed/webp/download) package.

Ubuntu/Debian:

```bash
apt-get install webp
```

If you plan to use [tinify](https://github.com/tinify/tinify-php):

```bash
composer require tinify/tinify
```

### Install via composer

```bash
composer require kudja/openmage-tinify
```

### Install form git

```bash
composer config repositories.openmage-tinify vcs https://github.com/kudja/openmage-tinify.git
composer require kudja/openmage-tinify:dev-master
```

### Install via modman

```bash
modman clone https://github.com/kudja/openmage-tinify.git
```

### Manual installation

Download zip and extract `src` folder to the magento root.

