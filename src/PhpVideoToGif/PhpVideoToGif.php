<?php
namespace PhpVideoToGif;
require 'vendor/autoload.php';

use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Format\Video\Gif;
use FFMpeg\Format\Video\X264;

class PhpVideoToGif
{
	private $ffmpeg;    
	private $imagick;
	private $directory = "screenshots";
	private $gifDir = "gifs";
	private $fontPath = "font/DejaVuSansCondensed-BoldOblique.ttf";
	private $fontSize = 14;

	public function __construct()
	{
		$this->ffmpeg = FFMpeg::create();
		$this->imagick = new Imagick();
	}

	public function convert($videoFilePath, $subFilePath, $dir = null)
	{
		if ($dir === null) {
			$dir = $this->gifDir;
		}
		if (!is_dir($this->gifDir)) {
			mkdir($this->gifDir, 0777, true);
		}

		if (!is_dir($this->directory)) {
			mkdir($this->directory, 0777, true);
		}

		$subs = file_get_contents($subFilePath);
		$subs = explode("\n", $subs);

		foreach ($subs as $i => $sub) {
			$sub = trim($sub);
			if (empty($sub)) {
				continue;
			}

			$start = $this->getSubtitleStartTime($sub);
			$end = $this->getSubtitleEndTime($sub);

			$gifFilename = $this->getGifFilename($i, $sub);

			if (file_exists($gifFilename)) {
				continue;
			} else {
				echo "Generating $gifFilename...\n";
				$this->makeGif($videoFilePath, $start, $end, $sub, $gifFilename);
			}
		}
	}

	private function getSubtitleStartTime($subtitle)
	{
		preg_match('/^([0-9:,]+)\s*-->\s*[0-9:,]+/', $subtitle, $matches);
		return isset($matches[1]) ? $matches[1] : null;
	}

	private function getSubtitleEndTime($subtitle)
	{
		preg_match('/[0-9:,]+\s*-->\s*([0-9:,]+)/', $subtitle, $matches);
		return isset($matches[1]) ? $matches[1] : null;
	}

	private function getGifFilename($index, $subtitle)
	{
		$subtitle = strip_tags($subtitle);
		$subtitle = preg_replace('/[^a-zA-Z0-9\s]/', '', $subtitle);
		return $this->gifDir . '/' . sprintf("%06d", $index) . '-' . slugify($subtitle) . '.gif';
	}

	private function makeGif($video, $start, $end, $subtitle, $output)
	{
		$videoPath = $this->extractVideoFrames($video, $start, $end);
		$imagePaths = $this->addTextToFrames($videoPath, $subtitle);
		$this->convertFramesToGif($imagePaths, $output);
		$this->cleanup($videoPath, $imagePaths);
	}

	private function extractVideoFrames($video, $start, $end)
	{
		$videoPath = $this->directory . '/video.mp4';

		$ffmpeg = $this->ffmpeg->open($video);
		$ffmpeg->filters()
			->clip(TimeCode::fromString($start), TimeCode::fromString($end));
		$ffmpeg->save(new X264(), $videoPath);

		return $videoPath;
	}

	private function addTextToFrames($videoPath, $subtitle)
	{
		$imagePaths = [];

		$ffmpeg = $this->ffmpeg->open($videoPath);
		$duration = $ffmpeg->getFFProbe()->format($videoPath)->get('duration');
		$interval = $duration / 10;

		$subtitles = explode("\n", strip_tags($subtitle));
		$textHeight = $this->calculateTextHeight();

		$frameIterator = $ffmpeg->getFrameIterator();
		$frameCount = 1;
		foreach ($frameIterator as $frame) {
			$imagePath = $this->directory . '/image-' . sprintf('%05d', $frameCount) . '.png';
			$frame->save($imagePath);
			$image = new Imagick($imagePath);
			$draw = new ImagickDraw();

			$draw->setFont($this->fontPath);
			$draw->setFontSize($this->fontSize);
			$draw->setTextAlignment(Imagick::ALIGN_CENTER);
			$draw->setFillColor(new ImagickPixel('#FFFFFF'));

			$frameHeight = $image->getImageHeight();
			foreach ($subtitles as $index => $subtitle) {
				$textWidth = $this->calculateTextWidth($subtitle);
				$x = $image->getImageWidth() / 2 - $textWidth / 2;
				$y = $frameHeight - ($textHeight * (count($subtitles) - $index)) - 5;
				$draw->setFillColor(new ImagickPixel('#000000'));
				$image->annotateImage($draw, $x - 1, $y - 1, 0, $subtitle);
				$image->annotateImage($draw, $x + 1, $y - 1, 0, $subtitle);
				$image->annotateImage($draw, $x - 1, $y + 1, 0, $subtitle);
				$image->annotateImage($draw, $x + 1, $y + 1, 0, $subtitle);
				$draw->setFillColor(new ImagickPixel('#FFFFFF'));
				$image->annotateImage($draw, $x, $y, 0, $subtitle);
			}

			$image->writeImage($imagePath);
			$imagePaths[] = $imagePath;
			$frameCount++;

			if ($frameCount % 10 === 0) {
				break; // Limit to 10 frames for testing purposes
			}
		}

		return $imagePaths;
	}

	private function calculateTextWidth($text)
	{
		$imagick = clone $this->imagick;
		$imagick->setFont($this->fontPath);
		$imagick->setFontSize($this->fontSize);
		$metrics = $imagick->queryFontMetrics($imagick, $text);
		return $metrics['textWidth'];
	}

	private function calculateTextHeight()
	{
		$imagick = clone $this->imagick;
		$imagick->setFont($this->fontPath);
		$imagick->setFontSize($this->fontSize);
		$metrics = $imagick->queryFontMetrics($imagick, "Text");
		return $metrics['textHeight'];
	}

	private function convertFramesToGif($imagePaths, $output)
	{
		$gifFormat = new Gif();
		$gifFormat->setLoop(0);

		$ffmpeg = $this->ffmpeg->open($imagePaths[0]);
		$video = $ffmpeg->addFromImages($imagePaths);
		$video->save($gifFormat, $output);
	}

	private function cleanup($videoPath, $imagePaths)
	{
		unlink($videoPath);

		foreach ($imagePaths as $imagePath) {
			unlink($imagePath);
		}

		rmdir($this->directory);
	}
}