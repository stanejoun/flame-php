<?php

namespace Stanejoun\LightPHP;

use Stanejoun\LightPHP\Exceptions\BusinessException;
use Stanejoun\LightPHP\Exceptions\ForbiddenException;

#[ModelDescription([
	'table' => 'file'
])]
class File extends AbstractModel
{
	public const AVAILABLE_MIME_TYPES = [
		// image
		'png' => 'image/png',
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'gif' => 'image/gif',
		'svg' => 'image/svg+xml',
		// audio
		'ogg' => 'audio/ogg',
		'mp3' => 'audio/mpeg',
		'wav' => 'audio/wav',
		// video
		'mp4' => 'video/mp4',
		'mpeg' => 'video/mpeg',
		'avi' => 'video/avi',
		'webm' => 'video/webm',
		// document
		'csv' => 'text/csv',
		'txt' => 'text/plain',
		'pdf' => 'application/pdf',
		'odt' => 'application/vnd.oasis.opendocument.text',
		'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		'xls' => 'application/pdf',
		'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'doc' => 'application/msword',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'ppt' => 'application/vnd.ms-powerpoint',
		'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'zip' => 'application/zip'
	];

	protected string $uid = '';
	protected string $name = '';
	protected string $path = '';
	protected string $filename = '';
	protected string $extension = '';
	protected string $mimeType = '';
	protected ?string $flag = null;
	protected int $fileSize = 0; //bytes
	protected ?string $thumbnail = null;
	protected bool $isPublic = false;
	protected bool $isDownloadable = false;
	protected bool $isDownloadableOnce = false;
	protected ?int $restrictToUserId = null;
	protected bool $restrictToConnectedUser = true;
	protected string $accessPermissions = '';
	protected ?array $metadata = null;
	protected ?\DateTime $createdAt = null;
	protected ?\DateTime $updatedAt = null;
	protected ?int $expiredAt = null;

	public function __construct(?array $definition = null)
	{
		if ($definition) {
			Helper::hydrate($this, $definition);
		}
	}

	public static function upload(string $inputName, string $storageLocation = '/', bool $public = false): self|array
	{
		$storageLocation = (str_starts_with($storageLocation, '/')) ? substr($storageLocation, 1) : $storageLocation;
		$storageLocation = (!empty($storageLocation) && $storageLocation !== '/' && !str_ends_with($storageLocation, '/')) ? "$storageLocation/" : $storageLocation;
		$path = ($public) ? DOCUMENT_ROOT . $storageLocation : FILES . $storageLocation;
		if (!file_exists($path)) {
			Helper::createFilesDirectory($path, $public);
		}
		if (Request::hasFiles($inputName)) {
			if (is_array($_FILES[$inputName]['name'])) {
				$files = [];
				foreach (array_keys($_FILES[$inputName]['name']) as $index) {
					$files[] = self::doUpload($inputName, $path, $public, $index);
				}
			} else {
				$files = self::doUpload($inputName, $path, $public);
			}
			unset($_FILES[$inputName]);
			return $files;
		}
		throw new BusinessException(Translator::translate('Unable to upload the file!'));
	}

	private static function doUpload(string $inputName, string $path, bool $public = false, ?int $index = null)
	{
		$file = new self();
		if (is_array($_FILES[$inputName]['name'])) {
			$index = ($index !== null) ? $index : 0;
			$tmpName = $_FILES[$inputName]['tmp_name'][$index];
			$filename = basename($_FILES[$inputName]['name'][$index]);
			$fileSize = filesize($_FILES[$inputName]['tmp_name'][$index]);
		} else {
			$tmpName = $_FILES[$inputName]['tmp_name'];
			$filename = basename($_FILES[$inputName]['name']);
			$fileSize = filesize($_FILES[$inputName]['tmp_name']);
		}
		$name = Helper::cleanFilename($filename);
		$extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
		$fileInfo = new \finfo(FILEINFO_MIME_TYPE);
		$mimeType = $fileInfo->file($tmpName);
		$accessPermissions = substr(sprintf('%o', fileperms($tmpName)), -4);
		if (!is_uploaded_file($tmpName)) {
			throw new \Exception('Unable to upload the file: "' . $tmpName . '"!');
		}
		$saveFileName = md5(uniqid('', true) . $name . Config::get('secretKey'));
		if (!move_uploaded_file($tmpName, $path . $saveFileName . '.' . $extension)) {
			throw new BusinessException([$name => Translator::translate('The file upload has failed!')]);
		}
		if (!file_exists($path . $saveFileName . '.' . $extension)) {
			throw new BusinessException(Translator::translate('The file upload has failed!'));
		}
		$filename = $path . $saveFileName . '.' . $extension;
		$name = basename($name, '.' . $extension);
		$file->hydrate([
			'name' => $name,
			'uid' => $saveFileName,
			'path' => $path,
			'filename' => $filename,
			'fileSize' => $fileSize,
			'mimeType' => $mimeType,
			'extension' => $extension,
			'accessPermissions' => $accessPermissions,
			'isPublic' => $public,
			'restrictToConnectedUser' => ($public) ? 0 : 1
		]);
		$file->save();
		return $file;
	}

	public function isRestrictToConnectedUser(): bool
	{
		return $this->restrictToConnectedUser;
	}

	public function setRestrictToConnectedUser(bool $restrictToConnectedUser): File
	{
		$this->restrictToConnectedUser = $restrictToConnectedUser;
		return $this;
	}

	public function beforeInsert(): void
	{
		$this->uid = md5('file-uid-' . bin2hex(random_bytes(6)) . '-' . date('YmdHis'));
	}

	public function getUid(): string
	{
		return $this->uid;
	}

	public function setUid(string $uid): File
	{
		$this->uid = $uid;
		return $this;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): File
	{
		$this->name = $name;
		return $this;
	}

	public function getPath(): string
	{
		return $this->path;
	}

	public function setPath(string $path): File
	{
		$this->path = $path;
		return $this;
	}

	public function getExtension(): string
	{
		return $this->extension;
	}

	public function setExtension(string $extension): File
	{
		$this->extension = $extension;
		return $this;
	}

	public function getMimeType(): string
	{
		return $this->mimeType;
	}

	public function setMimeType(string $mimeType): File
	{
		$this->mimeType = $mimeType;
		return $this;
	}

	public function getFlag(): ?string
	{
		return $this->flag;
	}

	public function setFlag(?string $flag): File
	{
		$this->flag = $flag;
		return $this;
	}

	public function getFileSize(): int
	{
		return $this->fileSize;
	}

	public function setFileSize(int $fileSize): File
	{
		$this->fileSize = $fileSize;
		return $this;
	}

	public function getThumbnail(): ?string
	{
		return $this->thumbnail;
	}

	public function setThumbnail(?string $thumbnail): File
	{
		$this->thumbnail = $thumbnail;
		return $this;
	}

	public function isPublic(): bool
	{
		return $this->isPublic;
	}

	public function setIsPublic(bool $isPublic): File
	{
		$this->isPublic = $isPublic;
		return $this;
	}

	public function isDownloadable(): bool
	{
		return $this->isDownloadable;
	}

	public function setIsDownloadable(bool $isDownloadable): File
	{
		$this->isDownloadable = $isDownloadable;
		return $this;
	}

	public function isDownloadableOnce(): bool
	{
		return $this->isDownloadableOnce;
	}

	public function setIsDownloadableOnce(bool $isDownloadableOnce): File
	{
		$this->isDownloadableOnce = $isDownloadableOnce;
		return $this;
	}

	public function getRestrictToUserId(): ?int
	{
		return $this->restrictToUserId;
	}

	public function setRestrictToUserId(?int $restrictToUserId): File
	{
		$this->restrictToUserId = $restrictToUserId;
		return $this;
	}

	public function getAccessPermissions(): string
	{
		return $this->accessPermissions;
	}

	public function setAccessPermissions(string $accessPermissions): File
	{
		$this->accessPermissions = $accessPermissions;
		return $this;
	}

	public function getMetadata(): ?array
	{
		return $this->metadata;
	}

	public function setMetadata(?array $metadata): File
	{
		$this->metadata = $metadata;
		return $this;
	}

	public function getCreatedAt(): ?\DateTime
	{
		return $this->createdAt;
	}

	public function setCreatedAt(?\DateTime $createdAt): File
	{
		$this->createdAt = $createdAt;
		return $this;
	}

	public function getUpdatedAt(): ?\DateTime
	{
		return $this->updatedAt;
	}

	public function setUpdatedAt(?\DateTime $updatedAt): File
	{
		$this->updatedAt = $updatedAt;
		return $this;
	}

	public function getExpiredAt(): ?int
	{
		return $this->expiredAt;
	}

	public function setExpiredAt(?int $expiredAt): File
	{
		$this->expiredAt = $expiredAt;
		return $this;
	}

	public function delete(): void
	{
		if (!unlink($this->path . $this->filename)) {
			throw new \Exception('Unable to delete this file: "' . $this->path . $this->filename . '"!');
		}
		parent::delete();
	}

	public function download(): void
	{
		if ($this->expiredAt !== null && $this->expiredAt < time()) {
			throw new ForbiddenException('Authorization to download this file has expired.');
		}
		if (!$this->isDownloadable) {
			throw new ForbiddenException('This file is not downloadable.');
		}
		if ($this->restrictToConnectedUser && !Authentication::$AUTHENTICATED_USER) {
			throw new ForbiddenException('The user must be connected to display this file');
		}
		if ($this->restrictToUserId && (!Authentication::$AUTHENTICATED_USER || Authentication::$AUTHENTICATED_USER->getId() !== $this->restrictToUserId)) {
			throw new ForbiddenException('This file is for another user.');
		}
		if (!preg_match('([a-zA-Z0-9./-]+)', $this->filename)) {
			throw new \InvalidArgumentException('Invalid filename!');
		}
		if (!in_array($this->mimeType, self::AVAILABLE_MIME_TYPES)) {
			throw new ForbiddenException('You are not allowed to download this file format.');
		}
		if ($this->isDownloadableOnce) {
			$this->isDownloadable = false;
			$this->expiredAt = time() - 1800;
			$this->save();
		}
		$path = $this->root() . $this->filename;
		$this->downloadHeader($path);
		readfile($path);
		exit;
	}

	private function root()
	{
		return ($this->isPublic) ? DOCUMENT_ROOT : FILES;
	}

	private function downloadHeader(string $filename): void
	{
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($filename));
	}

	public function display(string $media): void
	{
		if ($this->expiredAt !== null && $this->expiredAt < time()) {
			throw new ForbiddenException('Authorization to display this file has expired.');
		}
		if (!$this->restrictToConnectedUser && !Authentication::$AUTHENTICATED_USER) {
			throw new ForbiddenException('The user must be connected to display this file.');
		}
		if ($this->restrictToUserId && (!Authentication::$AUTHENTICATED_USER || Authentication::$AUTHENTICATED_USER->getId() !== $this->restrictToUserId)) {
			throw new ForbiddenException('This file is for another user.');
		}
		if (!preg_match('(image|sound|video)', $media) || !preg_match('([a-zA-Z0-9./-]+)', $this->filename)) {
			throw new \InvalidArgumentException('Check parameters for get the requested file!');
		}
		$path = $this->root() . $this->filename;
		$this->contentTypeHeader($this->mimeType);
		readfile($path);
		exit;
	}

	private function contentTypeHeader(string $mimeType): void
	{
		header('Content-Type: ' . $mimeType);
	}

	public function getFilename(): string
	{
		return $this->filename;
	}

	public function setFilename(string $filename): File
	{
		$this->filename = $filename;
		return $this;
	}
}