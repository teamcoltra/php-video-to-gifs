<?php
use PHPUnit\Framework\TestCase;
use TeamColtra\PhpVideoToGif\VideoToGifConverter;

class VideoToGifConverterTest extends TestCase
{
	protected $converter;

	protected function setUp(): void
	{
		$this->converter = new VideoToGifConverter();
	}
	
	public function testConvert()
{
	$videoFilePath = 'video/apollo17.mp4';
	$subFilePath = 'video/apollo17.srt';
	$gifFilename = 'gifs/000001-Subtitle.gif';

	// Clean up any existing GIF file
	if (file_exists($gifFilename)) {
		unlink($gifFilename);
	}

	// Assert that the 'gifs' directory doesn't exist initially
	$this->assertDirectoryNotExists('gifs');

	// Assert that the 'screenshots' directory doesn't exist initially
	$this->assertDirectoryNotExists('screenshots');

	// Call the convert() method
	$this->converter->convert($videoFilePath, $subFilePath);

	// Assert that the 'gifs' directory is created
	$this->assertDirectoryExists('gifs');

	// Assert that the 'screenshots' directory is created
	$this->assertDirectoryExists('screenshots');

	// Assert that the existing GIF file is skipped and no output is echoed
	$this->expectOutputString('');

	// Assert that the GIF file is generated
	$this->assertFileExists($gifFilename);
}

	public function testGetSubtitleStartTime()
	{
		$subtitle = '00:00:01,000 --> 00:00:02,000';
		$startTime = $this->converter->getSubtitleStartTime($subtitle);
		$this->assertEquals('00:00:01,000', $startTime);
	}

	public function testGetSubtitleEndTime()
	{
		$subtitle = '00:00:01,000 --> 00:00:02,000';
		$endTime = $this->converter->getSubtitleEndTime($subtitle);
		$this->assertEquals('00:00:02,000', $endTime);
	}

	public function testGetGifFilename()
	{
		$index = 1;
		$subtitle = '<b>Subtitle</b>';
		$expectedFilename = 'gifs/000001-Subtitle.gif';

		$filename = $this->converter->getGifFilename($index, $subtitle);
		$this->assertEquals($expectedFilename, $filename);
	}
}