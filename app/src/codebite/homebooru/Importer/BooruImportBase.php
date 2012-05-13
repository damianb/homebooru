<?php
namespace codebite\homebooru\Importer;
use \codebite\homebooru\Model\BooruPostModel;
use \emberlabs\openflame\Core\Internal\RuntimeException;

abstract class BooruImportBase
	implements ImporterInterface
{
	public $rating = BooruPostModel::RATING_UNKNOWN;
	public $md5, $tags, $source, $file_url;
	public $local_filename;

	abstract protected function getAPIURL();
	abstract protected function getLinkURL();

	public function fetch($id)
	{
		$xml = file_get_contents(sprintf($this->getAPIURL(), (int) $id));

		libxml_use_internal_errors(true);
		$xml = simplexml_load_string($xml);
		libxml_use_internal_errors(false);

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
		$this->source = (string) $xml->post[0]['source'] ?: sprintf($this->getLinkURL(), (int) $id);
		$this->tags = trim((string) $xml->post[0]['tags']);

		switch((string) $xml->post[0]['rating'])
		{
			case 's':
				$this->rating = BooruPostModel::RATING_SAFE;
			break;
			case 'q':
				$this->rating = BooruPostModel::RATING_QUESTIONABLE;
			break;
			case 'e':
				$this->rating = BooruPostModel::RATING_EXPLICIT;
			break;
			default:
				$this->rating = BooruPostModel::RATING_UNKNOWN;
		}
	}

	public function fetchImage()
	{
		$this->local_filename = $this->md5 . '.' . substr($this->file_url, strrpos($this->file_url, '.') + 1);
		file_put_contents(HOMEBOORU_IMAGE_IMPORT_ROOT . '/' . $this->local_filename, file_get_contents($this->file_url), LOCK_EX);
	}
}
