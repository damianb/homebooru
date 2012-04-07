<?php
namespace codebite\homebooru\Session;
use \R;
use \codebite\homebooru\WebKernel as Sigma;

class Session
	implements \ArrayAccess
{
	private $app, $_sid_entry, $sid, $session, $ipv4_validation_level, $ipv6_validation_level;

	public function __construct()
	{
		$this->app = Sigma::getInstance();
		$this->setIdentifier('COOKIE::' . $this->app->cookie->getCookiePrefix() . 'sid');
		$this->setIPv4ValidationLevel(3);
		$this->setIPv6ValidationLevel(6);
	}

	private function sid()
	{
		if(empty($this->sid))
		{
			$this->sid = $this->app->input->getInput($this->_sid_entry, '');
		}

		return $this->sid;
	}

	public function setIPv4ValidationLevel($level)
	{
		// maximum validation level is 4, because IPv4 has 4 chunks total.
		if($level < 1)
		{
			$level = 1;
		}
		elseif($level > 4)
		{
			$level = 4;
		}
		$this->ipv4_validation_level = (int) $level;
	}

	public function setIPv6ValidationLevel($level)
	{
		// maximum validation level is 8, because IPv6 has 8 chunks total.
		if($level < 1)
		{
			$level = 1;
		}
		elseif($level > 8)
		{
			$level = 8;
		}
		$this->ipv6_validation_level = (int) $level;
	}

	public function setIdentifier($sid)
	{
		$this->_sid_entry = $sid;
		$this->sid = NULL;
	}

	public function hasSession()
	{
		return $this->sid()->getWasSet();
	}

	public function loadSession()
	{
		if($this->hasSession())
		{
			$this->session = R::findOne('session', 'sid = ?', array($this->sid()->getClean()));
		}

		// validate session...
		if(!empty($this->session))
		{
			// check fingerprints..
			if($this->session->fingerprint != $this->getFingerprint())
			{
				$this->session = NULL;
			}
		}

		if(empty($this->session))
		{
			$seed = $this->app->seeder->buildRandomString(14);

			$this->session = R::dispense('session');

			$this->session->seed = $seed;
			$this->session->sid = $this->app->seeder->buildRandomString(32, $seed);
			$this->session->fingerprint = $this->getFingerprint();
			$this->session->useragent = $this->app->request->getUseragent();
			$this->session->ip = $this->app->request->getIP();

			R::store($this->session);

			$this->app->cookie->setCookie('sid')
				->setCookieValue($this->session->sid)
				->setExpireTime(0);
		}
	}

	public function getSID()
	{
		if(empty($this->session))
		{
			$this->loadSession();
		}

		return $this->session->sid;
	}

	public function getSessionSeed()
	{
		if(empty($this->session))
		{
			$this->loadSession();
		}

		return $this->session->seed;
	}

	private function getFingerprint()
	{
		$ip = $this->app->request->getIP();
		$is_ipv6 = (strpos($ip, ':') !== false) ? true : false;
		if($is_ipv6)
		{
			$validation_level = $this->ipv6_validation_level;
			$expand_ipv6 = (strpos($ip, '::') !== false) ? true : false;
		}
		else
		{
			$validation_level = $this->ipv4_validation_level;
		}
		$chunks = explode((($is_ipv6) ? ':' : '.'), $ip);

		if($is_ipv6 && $expand_ipv6)
		{
			$chunks = $this->expandIPv6($chunks);
		}

		return hash('sha256', $this->app->request->getUseragent() . implode('', array_slice($chunks, 0, $validation_level)) . $this->app['app.seed']);
	}

	private function expandIPv6($ip_chunks)
	{
		if($ip_chunks === array('', '', ''))
		{
			return array_fill(0, 8, '0'); // basically 0:0:0:0:0:0:0:0
		}
		else
		{
			$chunk_count = count($ip_chunks);
			for($i = 0; $i < 8; $i++)
			{
				if($ip_chunks[$i] == '')
				{
					if($i == 0) // at the start - ::ip
					{
						return array_merge(array_fill(0, 2, 0), array_slice($ip_chunks, 2));
					}
					elseif($i == 7) // at the end - ip::
					{
						return array_merge(array_slice($ip_chunks, 2), array_fill(0, 2, '0'));
					}
					else // in-between, ip::ip
					{
						// do the splits, drop the empty chunk in the middle, and use DARK MAGICK.
						// @note: (8 - ($chunk_count - 1)) is the number of IP chunks that the :: is covering for.
						array_splice($ip_chunks, $i, 1, array_fill($i, (8 - ($chunk_count - 1)), '0'));
						return $ip_chunks;
					}
				}
			}

			return $ip_chunks; // wtf bad data.
		}
	}

	public function __destruct()
	{
		$this->session->setMeta('tainted', true);
		R::store($this->session);
	}

	public function offsetExists($offset)
	{
		$storage = $this->session->getMeta('data.store');
		return isset($storage[$offset]) ? true : false;
	}

	public function offsetGet($offset)
	{
		$storage = $this->session->getMeta('data.store');
		if(!isset($storage[$offset]))
		{
			return NULL;
		}
		return $storage[$offset];
	}

	public function offsetSet($offset, $value)
	{
		$this->session->setMeta('data.store', array_merge((array) $this->session->getMeta('data.store'), array($offset => $value)));
	}

	public function offsetUnset($offset)
	{
		$this->session->setMeta('data.store', array_merge((array) $this->session->getMeta('data.store'), array($offset => NULL)));
	}
}
