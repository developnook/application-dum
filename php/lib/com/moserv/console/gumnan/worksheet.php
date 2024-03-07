<?php

require_once('com/moserv/console/gumnan/loader.php');
require_once('com/moserv/net/streaming.php');
require_once('org/fpdf/fpdf.php');
#require_once('com/tecnick/tcpdf/tcpdf.php');

class Worksheet {

	public static $mons = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');

	private $session;
	private $month;
	private $year;
	private $rows;

	public function __construct($session) {
		$this->session = $session;
	}

	public function setMonth($month) {
		$this->month = $month;
	}

	public function setYear($year) {
		$this->year = $year;
	}

	public function load() {
		$beginDate = sprintf('%04d-%02d-01', $this->year, $this->month);
		$endDate = date('Y-m-t', strtotime($beginDate));

		$loader = new PasscodeLoader($this->session);
		$loader->setBeginDate($beginDate);
		$loader->setEndDate($endDate);

		$loader->execute();

		$this->rows = $loader->getRows();
	}


	protected function chunk($rows, $limit = 500) {
		$chunk = array();

		$prev = null;
		$node = null;
		$date = null;
		$name = null;

		foreach ($rows as $row) {
			$name = $row['name'];
			$date = $row['date'];
			$sequence = $row['sequence'];
			$password = $row['password'];

			if ( $prev['date'] !== $date || (count($node) % $limit == 0) ) {
				$count = 0;

				if ($node != null && count($node) > 0)
					$chunk[] = array(
							'name'	=> $prev['name'],
							'date'	=> $prev['date'],
							'rows'	=> $node
					);

				$node = array();
				$prev = $row;
			}

			$node[] = $row;
		}


		if ($node != null && count($node) > 0) {
			$chunk[] = array(
				'name' => $name,
				'date' => $date,
				'rows' => $node
			);
			$node = null;
		}

		return $chunk;
	}
/*	

	protected function getHtml() {
		$buffer = array();
		$buffer[] = '<table border="1" cellpadding="0" cellspacing="0" width="65"><tr><th colspan="2" align="center"><b>001 - 050</b></th></tr>';
		$counter = 0;

		foreach ($this->rows as $row) {
			$buffer[] = sprintf('<tr><td height="10" width="55" valign="middle" align="center">%s - %s</td><td width="10"></td></tr>', $row['sequence'], $row['password']);

			if (++$counter > 500)
				break;
		}

		$buffer[] = '</table>';
		
		$html = implode('', $buffer);

		return $html;
	}


	public function output() {
		$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8');

		$pdf->setCreator('Moserv');
		$pdf->setAuthor('Chitsakun Suphasri');
		$pdf->SetTitle('Gumnan');
		$pdf->SetSubject('Gumnan');
		$pdf->SetKeywords('Gumnan, test');

		$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

#		$pdf->SetFont('helvetica', '', 5);
		$pdf->SetFont('times', '', 7);

#		$pdf->addTTFfont(dirname(__FILE__).'/../../font/calibri.ttf', 'TrueTypeUnicode', '', 8);
#		$pdf->SetFont('calibri', '', 8);

		$pdf->AddPage();

		$html = $this->getHtml();

		$pdf->writeHTML($html, true, false, false, false, '');

		$pdf->Output();
	}
*/

	public function page($pdf, $chunk, $capBottom = true, $indent = 0, $maxRow = 50, $maxCol = 10) {

		$name = $chunk['name'];
		$date = $chunk['date'];
		$rows = $chunk['rows'];
		$first = $rows[0]['sequence'];
		$last = $rows[count($rows) - 1]['sequence'];

		preg_match('/([0-9]{4})-([0-9]{2})-([0-9]{2})/', $date, $tokens);
		$year = $tokens[1] + 0;
		$month = self::$mons[$tokens[2] - 1];
		$day = $tokens[3] + 0;
		$dummy = sprintf('%s - Mon dd, Yyyy - [000 - 000]', $name);
		

		$pdf->AddPage();

		$pdf->SetXY($pdf->GetX(), $pdf->GetY() + 5.2);

		$pdf->SetTextColor(0, 0, 0);		# black
		$pdf->SetFillColor(235, 235, 235);	# light gray
		$pdf->SetFont('', 'BU');

#		$title = sprintf('Worksheet: %s %s (%03d - %03d)', $name, $date, $first, $last);
		$title = $name;
#		$pdf->Cell(23, 3.5, $title);
#		$pdf->Ln();
#		$pdf->Ln();
#		$pdf->Ln();


		$pdf->SetFont('', 'B');

		$col = 0;
		$ind = 0;

		while ($ind < count($rows)) {

			$lo = $ind;
			$hi = min($lo + $maxRow - 1, count($rows) - 1);

			$lseq = $rows[$lo]['sequence'];
			$hseq = $rows[$hi]['sequence'];

			if ($col > 0)
				$pdf->Cell(5);

			$text = sprintf('%03d - %03d', $lseq, $hseq);

			$pdf->Cell(23, 3.5, $text, 1, 0, 'C', true);

			$col++;
			$ind += $maxRow;
		}


		$pdf->SetFont('', '');

		$row = 0;

		while ($row < $maxRow) {

			$pdf->Ln();
			$col = 0;
			$end = false;

			while ($col < $maxCol && !$end) {
				if ($col > 0)
					$pdf->Cell(5);

				$ind = ($col * $maxRow) + $row;

				if ($ind < count($rows)) {
					$record = $rows[$ind];

					$text = sprintf('%03d - %05d', $record['sequence'], $record['password']);

					$pdf->Cell(18, 3.5, $text, 'LRB', 0, 'C');
					$pdf->Cell(5, 3.5, '', 'RB');

					$col++;
				}
				else
					$end = true;
			}

			$row++;
		}

		$caption = sprintf('%s - %s %2s, %04d - [%03d - %03d]', $name, $month, $day, $year, $first, $last);
		$pdf->SetTextColor(255, 255, 255);	# white
		$pdf->SetFillColor(0, 0, 0);		# black
		$captionWidth = $pdf->GetStringWidth($dummy) + 2;
		$pdf->SetXY(15 + ($indent * $captionWidth), ($capBottom)? 210 - 5: 0);
		$pdf->Cell(
			$captionWidth,	# width
			5,		# height
			$caption,	# text
			0,		# border
			0,		# ln
			'C',		# align
			true		# fill
		);
#		$this->test($pdf, $date);
	}

	public function test($pdf, $date) {
		$pdf->SetFont('', 'B');
#
#		$x = 5;
		$width = 50;
		$height = 4;
#		
#
#		for ($y = 0; $y <= 250; $y += 5) {
#			$pdf->SetXY($x, $y);
#			$text = 'y:'.$y;
#
#			$pdf->Cell($width, $height, $text);
#		}

		$pdf->SetXY(50, 210 - 5);
		$pdf->Cell(
			$width,		# width
			$height,	# height
			$date,		# text
			0,		# border
			0,		# ln
			'C',		# align
			true		# fill
		);
	}


	public function buildFile($filename) {
		$pdf = new FPDF('L');

		$pdf->SetFont('Arial', 'B', 7);
		$pdf->SetFillColor(235, 235, 235);
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetLineWidth(.3);
		$pdf->SetAutoPageBreak(false, 1.0);

		$colNo = 10;
		$rowNo = 50;
		$capBottom = true;

		$chunks = $this->chunk($this->rows);

		foreach ($chunks as $chunk) {

			$date = $chunk['date'];
			preg_match('/([0-9]{4})-([0-9]{2})-([0-9]{2})/', $date, $tokens);
			list($all, $yyyy, $mm, $dd) = $tokens;
			

			$this->page($pdf, $chunk, $capBottom, ($mm - 1) % 3);
			$capBottom = !$capBottom;
		}

		$pdf->Output($filename, 'F');
	}

	public function getFilename() {
		$dir = '/usr/project/buffer';

		$userId = $this->session->getVar('userId');

		$connection = $this->session->getConnection();
		$query = $connection->createQuery("
			select
				replace(lower(r.rest_name), ' ', '-') as rest_name
			from gumnan.restaurant r
				join gumnan.user_setting us using (rest_id)
			where us.user_id = ?
		");

		$query->setInt(1, $userId);

		$query->open();

		$rows = $query->getResultArray();

		if (count($rows) > 0) {
			$filename = sprintf(
				'%s/%s-%04d-%02d.pdf',
				$dir,
				$rows[0]['rest_name'],
				$this->year,
				$this->month
			);

			return $filename;
		}

		return null;
	}

	public function output() {
		global $_SERVER;
		$home = dirname(dirname($_SERVER['DOCUMENT_ROOT']));

		$filename = $this->getFilename();

		if (!file_exists($filename)) {
			$this->buildFile($filename);
		}

		$streaming = new Streaming();

		$streaming->setMimePath("{$home}/conf/mime.types");
		$streaming->setFilePath($filename);
		$streaming->setForce(true);
		$streaming->setBuffSize(500);
		$streaming->setRangeSupport(true);
		$streaming->execute();
	}
}


?>
