<?php

namespace Stanejoun\FlamePHP;

#[ModelDescription([
	'table' => 'file',
	'unmappedProperties' => ['_adapter']
])]
class ImageFile extends File
{
	public const AVAILABLE_MIME_TYPES = [
		'png' => 'image/png',
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'gif' => 'image/gif',
		'svg' => 'image/svg+xml'
	];

	private ?\Intervention\Image\Image $_adapter = null;

	public static function uploadAndCreateThumbnail(string $inputName, int $width = 220, ?int $height = null, string $storageLocation = '/'): self|array
	{
		$imageFile = self::upload($inputName, $storageLocation);
		$imageFile->createThumbnail($width, $height);
		return $imageFile;
	}

	public function createThumbnail(int $width = 220, ?int $height = null): void
	{
		$this->resize($width, $height);
		$this->thumbnail = $this->root() . $this->path . $this->uid . '_thumb.' . $this->extension;
		$this->adapter()->save($this->thumbnail);
		$this->save();
	}

	public function resize(int $width = 220, ?int $height = null)
	{
		$image = $this->adapter();
		$image->resize($width, $height, function ($constraint) {
			$constraint->aspectRatio();
			$constraint->upsize();
		});
	}

	public function adapter(): \Intervention\Image\Image
	{
		if (!isset($this->_adapter)) {
			$this->_adapter = \Intervention\Image\ImageManagerStatic::make($this->path . $this->uid . $this->extension);
		}
		return $this->_adapter;
	}

	public function delete(): void
	{
		if ($this->thumbnail) {
			$this->removeThumbnail();
		}
		parent::delete();
	}

	public function removeThumbnail()
	{
		$thumbnail = $this->thumbnail;
		if (file_exists($thumbnail) && !unlink($thumbnail)) {
			throw new \Exception('Unable to delete thumbnail!');
		}
		$this->thumbnail = null;
		$this->save();
	}
}