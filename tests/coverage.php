<?php

class code_coverage
{
	protected $dataFile = null;

	public function __construct($dataFile)
	{
		$this->dataFile = $dataFile;
	}

	public function createReports($exportDir, $baseDir)
	{
		if (!file_exists($this->dataFile)) {
			$this->saveRawData();
		}

		require_once $this->dataFile;

		foreach ($analysis as $filename => $coverage) {
			if (substr($filename, 0, strlen($baseDir)) != $baseDir) continue;

			$basename = substr($filename, strlen($baseDir) + 1);
			$html     = <<<HTML
<html>
<head>
	<title>Coverage: $basename</title>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
	<style type="text/css">
	code {font-family: Monacos, Consolas, monospace; }
	code .covered { color: green; }
	code .missed { color: red; }
	code .dead { color: blue; }
	code span.l { color: #999; }
	</style>
</head>
<body>

<h1>Coverage: $basename</h1>

<pre><code>
HTML;

			$lines     = array_map('rtrim', file($filename));
			$lineNrLen = strlen(count($lines));

			foreach ($lines as $idx => $line) {
				$line  = str_replace("\t", '   ', $line);
				$class = $this->lineCoverageCodeToStyleClass($coverage, $idx+1);
				$html .= '<span class="'.$class.'"><span class="l">'.str_pad($idx+1, $lineNrLen, '0', STR_PAD_LEFT).'</span> '.htmlspecialchars($line).'</span>'."\n";
			}

			$html .= '</code></pre></body></html>';
			file_put_contents($exportDir.'/'.str_replace(DIRECTORY_SEPARATOR, '_', $basename).'.html', $html);
		}
	}

	public function saveRawData()
	{
		file_put_contents($this->dataFile, '<?php $analysis = '.var_export(xdebug_get_code_coverage(), true).';');
		xdebug_stop_code_coverage(true);
	}

	protected function lineCoverageCodeToStyleClass($coverage, $line) {
		if (!array_key_exists($line, $coverage)) {
			return 'comment';
		}

		$code = $coverage[$line];
		if (empty($code)) return 'comment';

		switch ($code) {
			case -1: return 'missed';
			case -2: return 'dead';
		}

		return 'covered';
	}
}
