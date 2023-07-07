# PHP Video to GIF

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A video to GIF converter package using PHP-FFMpeg and Imagick.

## Features

- Convert video files to animated GIFs with subtitles
- Customizable text styling and positioning (TODO)
- Supports various video formats

## Requirements

- PHP 8.1 or later
- FFmpeg (with PHP-FFMpeg library)
- ImageMagick (with Imagick PHP extension)

## Intallation
```shell
composer require teamcoltra/php-video-to-gif
```

## Usage
```php
use TeamColtra\PhpVideoToGif\VideoToGifConverter;

$converter = new VideoToGifConverter();
$converter->convert('path/to/video.mp4', 'path/to/subtitles.srt','path/to/output');
```
Your output path defaults to gif/ and will create the directory if it doesn't already exist.


## Contributing

Contributions are welcome! Please feel free to submit a pull request or open an issue for any bugs or feature requests.

## License

This package is open source and available under the [MIT License](LICENSE).