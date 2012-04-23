<?php
namespace codebite\homebooru\Runtime;

if(!defined('SHOT_ROOT')) exit;

final class ExceptionHandler
{
	protected $exception;

	public static function invoke($e)
	{
		$self = new self($e);
	}

	public function __construct(\Exception $e)
	{
		if(HOMEBOORU_EXHANDLER_UNWRAP && HOMEBOORU_EXHANDLER_UNWRAP > 0)
		{
			for($i = 0; $i < (int) HOMEBOORU_EXHANDLER_UNWRAP; $i++)
			{
				$previous = $e->getPrevious();
				if($previous === NULL)
				{
					break;
				}
				$e = $previous;
			}
		}

		$this->exception = $e;

		$page = $this->getTemplate('exception_header.twig.html');
		if(SHOT_DEBUG)
		{
			$search = array(
				'{{ error_string }}',
				'{{ error_message }}',
				'{{ error_code }}',
				'{{ error_type }}',
				'{{ error_trace }}',
				'{{ error_file }}',
				'{{ error_line }}',
				'{{ error_context }}',
				SHOT_ROOT,
				ucfirst(SHOT_ROOT),
			);
			$replace = array(
				htmlentities(get_class($e), ENT_QUOTES, 'UTF-8') . ($e->getCode() ?: ''),
				htmlspecialchars($e->getMessage() ?: 'NULL', ENT_QUOTES, 'UTF-8'),
				(int) ($e->getCode() ?: 0),
				htmlspecialchars(get_class($e), ENT_QUOTES, 'UTF-8'),
				nl2br(str_replace('):', "):\n&nbsp;&nbsp;&nbsp;&nbsp;", htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8'))),
				htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8'),
				(int) $e->getLine(),
				$this->highlightCode($this->getCodeContext($e->getFile(), $e->getLine(), 8)),
				'',
				'',
			);
			$page .= str_replace($search, $replace, $this->getTemplate('exception_dump.twig.html'));
		}
		else
		{
			$search = array(
				'{{ error_string }}',
				SHOT_ROOT,
				ucfirst(SHOT_ROOT),
			);
			$replace = array(
				htmlspecialchars(get_class($e), ENT_QUOTES, 'UTF-8') . ($e->getCode() ?: ''),
				'',
				'',
			);
			$page .= str_replace($search, $replace, $this->getTemplate('exception_brief.twig.html'));
		}
		$page .= $this->getTemplate('exception_footer.twig.html');

		// Dump page back to user.
		echo $page;

		// Flush all output buffers before exit.
		while (@ob_end_flush());
		exit;
	}

	public function getTemplate($template)
	{
		// all templates for exception notices are self-contained here to remove a dependency on twig
		// ...so we don't have problems telling the user that we just had problems. :P
		$tpl = array();
		switch($template)
		{
			case 'exception_header.twig.html':
				$tpl[] = 'PCFET0NUWVBFIGh0bWw+CjwhLS0KCUNvcHlyaWdodCAoYykgMjAxMiBjb2RlYml0ZS5uZXQKCglPcGVu';
				$tpl[] = 'LXNvdXJjZWQgYW5kIGF2YWlsYWJsZSB1bmRlciB0aGUgTUlUIGxpY2Vuc2UKCWh0dHA6Ly93d3cub3Bl';
				$tpl[] = 'bnNvdXJjZS5vcmcvbGljZW5zZXMvTUlUCgoJaHR0cHM6Ly9naXRodWIuY29tL2RhbWlhbmIvaG9tZWJv';
				$tpl[] = 'b3J1Ci0tPgo8aHRtbCBsYW5nPSJlbi11cyIgZGlyPSJsdHIiPgo8aGVhZD4KCTxtZXRhIGNoYXJzZXQ9';
				$tpl[] = 'InV0Zi04Ij4KCTx0aXRsZT5HZW5lcmFsIEVycm9yPC90aXRsZT4KCTxzdHlsZSB0eXBlPSJ0ZXh0L2Nz';
				$tpl[] = 'cyI+CgkJYm9keSB7IGJhY2tncm91bmQtY29sb3I6ICNFQkVCRUI7IH0KCQkud3JhcCB7IHdpZHRoOiA5';
				$tpl[] = 'NDBweDsgbWFyZ2luOiA1MHB4IGF1dG87IGZvbnQtZmFtaWx5OiAiRHJvaWQgU2FucyIsIHNhbnMtc2Vy';
				$tpl[] = 'aWY7IH0KCQkuY29udGFpbmVyIHsgYm9yZGVyOiAycHggc29saWQgI0M0QzRDNDsgYm9yZGVyLXJhZGl1';
				$tpl[] = 'czogMTRweDsgcGFkZGluZzogMCAyMHB4OyBiYWNrZ3JvdW5kLWNvbG9yOiAjRkZGOyB9CgkJLmVycm9y';
				$tpl[] = 'IHsgbGluZS1oZWlnaHQ6IDI0cHg7IHBhZGRpbmc6IDE1cHggMDsgfSAuZXJyb3IgaDIgeyBtYXJnaW46';
				$tpl[] = 'IDAgMCAxMHB4IDA7IHBhZGRpbmc6IDAgMCA1cHggMDsgY29sb3I6ICNiMDA7IGJvcmRlci1ib3R0b206';
				$tpl[] = 'IDFweCBzb2xpZCAjRDNBOUE5OyB9IC5lcnJvcnN0cmluZyB7IGZvbnQtZmFtaWx5OiAiRHJvaWQgU2Fu';
				$tpl[] = 'cyBNb25vIiwgbW9ub3NwYWNlOyBmb250LXNpemU6IDE0cHg7IH0KCQkuY29kZSB7IGZvbnQtZmFtaWx5';
				$tpl[] = 'OiAiRHJvaWQgU2FucyBNb25vIiwgbW9ub3NwYWNlOyBib3JkZXI6IDFweCBzb2xpZCAjRDYwMDAwOyBi';
				$tpl[] = 'YWNrZ3JvdW5kLWNvbG9yOiAjRkZGMkYyOyBmb250LXNpemU6IDExcHg7IG1hcmdpbjogMTBweDsgcGFk';
				$tpl[] = 'ZGluZzogNXB4OyBsaW5lLWhlaWdodDogMThweDsgfQoJCS50cmFjZSB7IHBhZGRpbmc6IDVweCAxMHB4';
				$tpl[] = 'OyB9CgkJLnN5bnRheGJnIHsgY29sb3I6ICNGRkZGRkY7IH0gLnN5bnRheGNvbW1lbnQgeyBjb2xvcjog';
				$tpl[] = 'I0ZGODAwMDsgfSAuc3ludGF4ZGVmYXVsdCB7IGNvbG9yOiAjMDAwMEJCOyB9IC5zeW50YXhodG1sIHsg';
				$tpl[] = 'Y29sb3I6ICMwMDAwMDA7IH0gLnN5bnRheGtleXdvcmQgeyBjb2xvcjogIzAwNzcwMDsgfSAuc3ludGF4';
				$tpl[] = 'c3RyaW5nIHsgY29sb3I6ICNERDAwMDA7IH0KCQlmb290ZXIgeyBib3JkZXItdG9wOiAxcHggc29saWQg';
				$tpl[] = 'I2UxZTFlMTsgZm9udC1zaXplOiAxMXB4OyBwYWRkaW5nOiAxMHB4OyBtYXJnaW4tdG9wOiAyMHB4OyB9';
				$tpl[] = 'IGZvb3RlciA+IGEgeyBjb2xvcjogI0IwMDsgfSBmb290ZXIgPiBhOmhvdmVyLCBmb290ZXIgPiBhOmFj';
				$tpl[] = 'dGl2ZSwgZm9vdGVyID4gYTpmb2N1cyB7IGNvbG9yOiAjODAwOyB9Cgk8L3N0eWxlPgo8L2hlYWQ+Cjxi';
				$tpl[] = 'b2R5PgoJPGRpdiBjbGFzcz0id3JhcCI+CgkJPGRpdiBjbGFzcz0iY29udGFpbmVyIj4KCQkJPGRpdiBj';
				$tpl[] = 'bGFzcz0iZXJyb3IiPg==';
			break;

			case 'exception_footer.twig.html':
				$tpl[] = 'CQkJPC9kaXY+CgkJCTxmb290ZXI+CgkJCQlwb3dlcmVkIGJ5IDxhIGhyZWY9Imh0dHBzOi8vZ2l0aHVi';
				$tpl[] = 'LmNvbS9kYW1pYW5iL2hvbWVib29ydSI+PHN0cm9uZz5jb2RlYml0ZVxob21lYm9vcnU8L3N0cm9uZz48';
				$tpl[] = 'L2E+ICZjb3B5OyAyMDEyIDxhIGhyZWY9Imh0dHA6Ly9jb2RlYml0ZS5uZXQvIj5jb2RlYml0ZS5uZXQ8';
				$tpl[] = 'L2E+CgkJCTwvZm9vdGVyPgoJCTwvZGl2PgoJPC9kaXY+CjwvYm9keT4KPC9odG1sPg==';
			break;

			case 'exception_dump.twig.html':
				$tpl[] = 'CQkJCTxoMj5HZW5lcmFsIEVycm9yPC9oMj4KCQkJCTxkaXY+VW5oYW5kbGVkIGV4Y2VwdGlvbjogJnF1';
				$tpl[] = 'b3Q7PHNwYW4gY2xhc3M9ImVycm9yc3RyaW5nIj57eyBlcnJvcl90eXBlIH19KHt7IGVycm9yX2NvZGUg';
				$tpl[] = 'fX0pPC9zcGFuPiZxdW90OzwvZGl2PgoJCQkJPGRpdj5FeGNlcHRpb24gbWVzc2FnZTogJnF1b3Q7PGVt';
				$tpl[] = 'Pnt7IGVycm9yX21lc3NhZ2UgfX08L2VtPiZxdW90OzwvZGl2PgoJCQkJPGJyPgoJCQkJPGRpdj5FeGNl';
				$tpl[] = 'cHRpb24gdHJhY2U6IDxicj4KCQkJCQk8ZGl2IGNsYXNzPSJjb2RlIHRyYWNlIj57eyBlcnJvcl90cmFj';
				$tpl[] = 'ZSB9fTwvZGl2PgoJCQkJPC9kaXY+CgkJCQk8YnI+CgkJCQk8ZGl2PkNvbnRleHQ6IDxicj4KCQkJCQk8';
				$tpl[] = 'ZGl2IGNsYXNzPSJjb2RlIGNvbnRleHQiPnt7IGVycm9yX2NvbnRleHQgfX08L2Rpdj4KCQkJCTwvZGl2';
				$tpl[] = 'Pg==';
			break;

			case 'exception_brief.twig.html':
				$tpl[] = 'CQkJCTxoMj5HZW5lcmFsIEVycm9yPC9oMj4KCQkJCTxkaXY+VW5oYW5kbGVkIGV4Y2VwdGlvbjogJnF1';
				$tpl[] = 'b3Q7PHNwYW4gY2xhc3M9ImVycm9yc3RyaW5nIj57eyBlcnJvcl9zdHJpbmcgfX08L3NwYW4+JnF1b3Q7';
				$tpl[] = 'PC9kaXY+CgkJCQk8ZGl2Pk1vcmUgaW5mb3JtYXRpb24gcmVnYXJkaW5nIHRoaXMgZXJyb3IgY2FuIGJl';
				$tpl[] = 'IG9idGFpbmVkIGJ5IGVuYWJsaW5nIHRoZSBhcHBsaWNhdGlvbiZhcG9zO3MgZGVidWcgbW9kZS48L2Rp';
				$tpl[] = 'dj4=';
			break;
		}

		return ($tpl) ? base64_decode(implode('', $tpl)) : '';
	}

	protected function getCodeContext($file, $line, $context)
	{
		$return = '';
		foreach (file($file) as $i => $str)
		{
			if (($i + 1) > ($line - $context))
			{
				if(($i + 1) > ($line + $context))
				{
					break;
				}
				$return .= $str;
			}
		}

		return $return;
	}

	protected function highlightCode($code)
	{
		$remove_tags = false;
		if (!preg_match('#\<\?.*?\?\>#is', $code))
		{
			$remove_tags = true;
			$code = "<?php $code";
		}

		$conf = array('highlight.bg', 'highlight.comment', 'highlight.default', 'highlight.html', 'highlight.keyword', 'highlight.string');
		foreach ($conf as $ini_var)
		{
			@ini_set($ini_var, str_replace('highlight.', 'syntax', $ini_var));
		}

		$code = highlight_string($code, true);

		$str_from = array('<span style="color: ', '<font color="syntax', '</font>', '<code>', '</code>','[', ']', '.', ':');
		$str_to = array('<span class="', '<span class="syntax', '</span>', '', '', '&#91;', '&#93;', '&#46;', '&#58;');

		if ($remove_tags)
		{
			$str_from[] = '<span class="syntaxdefault">&lt;?php </span>';
			$str_to[] = '';
			$str_from[] = '<span class="syntaxdefault">&lt;?php&nbsp;';
			$str_to[] = '<span class="syntaxdefault">';
		}

		$code = str_replace($str_from, $str_to, $code);
		$code = preg_replace('#^(<span class="[a-z_]+">)\n?(.*?)\n?(</span>)$#is', '$1$2$3', $code);

		$code = preg_replace('#^<span class="[a-z]+"><span class="([a-z]+)">(.*)</span></span>#s', '<span class="$1">$2</span>', $code);
		$code = preg_replace('#(?:\s++|&nbsp;)*+</span>$#u', '</span>', $code);

		// remove newline at the end
		$code = rtrim($code, "\n");

		return $code;
	}

	/**
	 * used in conjunction with self::getTemplate()
	 */
	protected function buildTemplate($tpl)
	{
		return str_split(base64_encode($tpl), 80);

		/**
		 * possibly useful:
		 *

		$files = array(
			'exception_header',
			'exception_footer',
			'exception_dump',
			'exception_brief',
		);

		foreach($files as $file){
			printf('<br>%s.twig.html<br><br>', $file);
			foreach(str_split(base64_encode(trim(file_get_contents(SHOT_ROOT . '/develop/exception/' . $file . '.twig.html'), "\n")), 80) as $line)
				printf('$tpl[] = \'%s\';<br>', $line);
		}

		*/
	}
}
