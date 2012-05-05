<?php
namespace codebite\homebooru\Importer;
use \emberlabs\openflame\Core\Internal\RuntimeException;

class Gelbooru
{
	const POST_URL = 'http://gelbooru.com/index.php?page=post&s=view&id=%d';
	const API_URL = 'http://gelbooru.com/index.php?page=dapi&s=post&q=index&id=%d';

	protected $remote_id = 0;
	public $rating = self::RATING_UNKNOWN;
	public $md5, $tags, $source, $file_url;
	public $local_filename;

	const RATING_SAFE = 2;
	const RATING_QUESTIONABLE = 3;
	const RATING_EXPLICIT = 4;
	const RATING_UNKNOWN = 5;

	public function fetch($id)
	{
		$this->remote_id = $id;
		$this->interpretXML(file_get_contents(sprintf(self::API_URL, (int) $this->remote_id)));
	}

	public function fetchImage()
	{
		$this->local_filename = $this->md5 . '.' . substr($this->file_url, strrpos($this->file_url, '.') + 1);
		file_put_contents(HOMEBOORU_IMAGE_IMPORT_ROOT . '/' . $this->local_filename, file_get_contents($this->file_url), LOCK_EX);
	}

	private function getRemoteSource($remote_id)
	{
		return self::POST_URL . $remote_id;
	}

	private function interpretXML($xml)
	{
		libxml_use_internal_errors(true);
		$xml = simplexml_load_string($xml);

		if($xml === false)
		{
			throw new RuntimeException('XML interpretation failed');
		}

		if($xml['count'] < 1)
		{
			throw new RuntimeException('Invalid remote ID specified');
		}

		$this->file_url = (string) $xml->post[0]['file_url'];
		$this->md5 = (string) $xml->post[0]['md5'];
		$this->source = (string) $xml->post[0]['source'] ?: sprintf(self::POST_URL, (int) $this->remote_id);
		$this->tags = trim((string) $xml->post[0]['tags']);

		switch((string) $xml->post[0]['rating'])
		{
			case 's':
				$this->rating = self::RATING_SAFE;
			break;
			case 'q':
				$this->rating = self::RATING_QUESTIONABLE;
			break;
			case 'e':
				$this->rating = self::RATING_EXPLICIT;
			break;
			default:
				$this->rating = self::RATING_UNKNOWN;
		}
	}
}
