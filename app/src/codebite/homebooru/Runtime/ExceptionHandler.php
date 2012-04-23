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

		$page = $this->getTemplate('layout_header');
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
			);
			$replace = array(
				get_class($e) . ($e->getCode() ?: ''),
				$e->getMessage() ?: 'NULL',
				$e->getCode() ?: 0,
				get_class($e),
				nl2br(str_replace(array(SHOT_ROOT, '):'), array('', "):\n&nbsp;&nbsp;&nbsp;&nbsp;"), $e->getTraceAsString())),
				$e->getFile(),
				$e->getLine(),
				$this->highlightCode($this->getCodeContext($e->getFile(), $e->getLine(), 6)),
			);
			$page .= str_replace($search, $replace, $this->getTemplate('dump'));
		}
		else
		{
			$search = array(
				'{{ error_string }}',
			);
			$replace = array(
				get_class($e) . ($e->getCode() ?: ''),
			);
			$page .= str_replace($search, $replace, $this->getTemplate('error'));
		}
		$page .= $this->getTemplate('layout_footer');

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
			case 'layout_header':
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
				$tpl[] = 'IDFweCBzb2xpZCAjRDNBOUE5OyB9CgkJLmNvZGUgeyBmb250LWZhbWlseTogIkRyb2lkIFNhbnMgTW9u';
				$tpl[] = 'byIsIG1vbm9zcGFjZTsgYm9yZGVyOiAxcHggc29saWQgI0Q2MDAwMDsgYmFja2dyb3VuZC1jb2xvcjog';
				$tpl[] = 'I0ZGRjJGMjsgZm9udC1zaXplOiAxMXB4OyBtYXJnaW46IDEwcHg7IHBhZGRpbmc6IDVweDsgbGluZS1o';
				$tpl[] = 'ZWlnaHQ6IDE4cHg7IH0KCQkudHJhY2UgeyBwYWRkaW5nOiA1cHggMTBweDsgfQoJCS5zeW50YXhiZyB7';
				$tpl[] = 'IGNvbG9yOiAjRkZGRkZGOyB9IC5zeW50YXhjb21tZW50IHsgY29sb3I6ICNGRjgwMDA7IH0gLnN5bnRh';
				$tpl[] = 'eGRlZmF1bHQgeyBjb2xvcjogIzAwMDBCQjsgfSAuc3ludGF4aHRtbCB7IGNvbG9yOiAjMDAwMDAwOyB9';
				$tpl[] = 'IC5zeW50YXhrZXl3b3JkIHsgY29sb3I6ICMwMDc3MDA7IH0gLnN5bnRheHN0cmluZyB7IGNvbG9yOiAj';
				$tpl[] = 'REQwMDAwOyB9CgkJZm9vdGVyIHsgYm9yZGVyLXRvcDogMXB4IHNvbGlkICNlMWUxZTE7IGZvbnQtc2l6';
				$tpl[] = 'ZTogMTFweDsgcGFkZGluZzogMTBweDsgbWFyZ2luLXRvcDogMjBweDsgfSBmb290ZXIgPiBhIHsgY29s';
				$tpl[] = 'b3I6ICNCMDA7IH0gZm9vdGVyID4gYTpob3ZlciwgZm9vdGVyID4gYTphY3RpdmUsIGZvb3RlciA+IGE6';
				$tpl[] = 'Zm9jdXMgeyBjb2xvcjogIzgwMDsgfQoJPC9zdHlsZT4KPC9oZWFkPgo8Ym9keT4KCTxkaXYgY2xhc3M9';
				$tpl[] = 'IndyYXAiPgoJCTxkaXYgY2xhc3M9ImNvbnRhaW5lciI+CgkJCTxkaXYgY2xhc3M9ImVycm9yIj4=';
			break;

			case 'layout_footer':
				$tpl[] = 'CQkJPC9kaXY+CgkJCTxmb290ZXI+CgkJCQlwb3dlcmVkIGJ5IDxhIGhyZWY9Imh0dHBzOi8vZ2l0aHVi';
				$tpl[] = 'LmNvbS9kYW1pYW5iL2hvbWVib29ydSI+PHN0cm9uZz5jb2RlYml0ZVxob21lYm9vcnU8L3N0cm9uZz48';
				$tpl[] = 'L2E+ICZjb3B5OyAyMDEyIDxhIGhyZWY9Imh0dHA6Ly9jb2RlYml0ZS5uZXQvIj5jb2RlYml0ZS5uZXQ8';
				$tpl[] = 'L2E+CgkJCTwvZm9vdGVyPgoJCTwvZGl2PgoJPC9kaXY+CjwvYm9keT4KPC9odG1sPg==';
			break;

			case 'dump':
				$tpl[] = 'CQkJCTxoMj5HZW5lcmFsIEVycm9yPC9oMj4KCQkJCTxkaXY+VW5oYW5kbGVkIGV4Y2VwdGlvbjogJnF1';
				$tpl[] = 'b3Q7e3sgZXJyb3JfdHlwZSB9fSh7eyBlcnJvcl9jb2RlIH19KSZxdW90OzwvZGl2PgoJCQkJPGRpdj5F';
				$tpl[] = 'eGNlcHRpb24gbWVzc2FnZTogJnF1b3Q7PGVtPnt7IGVycm9yX21lc3NhZ2UgfX08L2VtPiZxdW90Ozwv';
				$tpl[] = 'ZGl2PgoJCQkJPGJyPgoJCQkJPGRpdj5FeGNlcHRpb24gdHJhY2U6IDxicj4KCQkJCQk8ZGl2IGNsYXNz';
				$tpl[] = 'PSJjb2RlIHRyYWNlIj57eyBlcnJvcl90cmFjZSB9fTwvZGl2PgoJCQkJPC9kaXY+CgkJCQk8YnI+CgkJ';
				$tpl[] = 'CQk8ZGl2PkNvbnRleHQ6IDxicj4KCQkJCQk8ZGl2IGNsYXNzPSJjb2RlIGNvbnRleHQiPnt7IGVycm9y';
				$tpl[] = 'X2NvbnRleHQgfX08L2Rpdj4KCQkJCTwvZGl2Pg==';
			break;

			case 'error':
				$tpl[] = 'CQkJCTxoMj5HZW5lcmFsIEVycm9yPC9oMj4KCQkJCTxkaXY+VW5oYW5kbGVkIGV4Y2VwdGlvbjogJnF1';
				$tpl[] = 'b3Q7e3sgZXJyb3Jfc3RyaW5nIH19JnF1b3Q7PC9kaXY+CgkJCQk8ZGl2Pk1vcmUgaW5mb3JtYXRpb24g';
				$tpl[] = 'cmVnYXJkaW5nIHRoaXMgZXJyb3IgY2FuIGJlIG9idGFpbmVkIGJ5IGVuYWJsaW5nIHRoZSBhcHBsaWNh';
				$tpl[] = 'dGlvbiZhcG9zO3MgZGVidWcgbW9kZS48L2Rpdj4=';
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
	}
}
