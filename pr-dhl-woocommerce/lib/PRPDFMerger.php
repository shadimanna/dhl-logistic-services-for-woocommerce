<?php
if( !class_exists( 'PDFMerger' ) ){
	require_once( 'PDFMerger/PDFMerger.php' );
}

class PRPDFMerger extends PDFMerger{

	private $_files;	//['form.pdf']  ["1,2,4, 5-19"]
	private $_fpdi;
	
	/**
	 * Merge PDFs.
	 * @return void
	 */
	public function __construct()
	{
		if( !class_exists( 'FPDF') ){
			require_once('PDFMerger/fpdf/fpdf.php');	
		}
		
		if( !class_exists( 'FPDI' ) ){
			require_once('PDFMerger/fpdi/fpdi.php');
		}
		
	}

	/**
	 * FPDI uses single characters for specifying the output location. Change our more descriptive string into proper format.
	 * @param $mode
	 * @return Character
	 */
	private function _switchmode($mode)
	{
		switch(strtolower($mode))
		{
			case 'download':
				return 'D';
				break;
			case 'browser':
				return 'I';
				break;
			case 'file':
				return 'F';
				break;
			case 'string':
				return 'S';
				break;
			default:
				return 'I';
				break;
		}
	}
	
	/**
	 * Takes our provided pages in the form of 1,3,4,16-50 and creates an array of all pages
	 * @param $pages
	 * @return unknown_type
	 */
	private function _rewritepages($pages)
	{
		$pages = str_replace(' ', '', $pages);
		$part = explode(',', $pages);
		
		//parse hyphens
		foreach($part as $i)
		{
			$ind = explode('-', $i);

			if(count($ind) == 2)
			{
				$x = $ind[0]; //start page
				$y = $ind[1]; //end page
				
				if($x > $y): throw new exception("Starting page, '$x' is greater than ending page '$y'."); return false; endif;	
				
				//add middle pages
				while($x <= $y): $newpages[] = (int) $x; $x++; endwhile;
			}
			else
			{
				$newpages[] = (int) $ind[0];
			}
		}
		
		return $newpages;
	}
}