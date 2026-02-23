<!-- <?php
/*******************************************************************************
* FPDF class (Minimal Version for your project)                               *
*******************************************************************************/
//define('FPDF_VERSION','1.86');

// class FPDF
// {
//     protected $page;               // current page number
//     protected $n;                  // current object number
//     protected $offsets;            // array of object offsets
//     protected $buffer;             // buffer holding in-memory PDF
//     protected $pages;              // array containing pages
//     protected $state;              // current debugger state
//     protected $compress;           // compression flag
//     protected $k;                  // scale factor (number of points in user unit)
//     protected $CurOrientation;     // current orientation
//     protected $DefOrientation;     // default orientation
//     protected $CurPageSize;        // current page size
//     protected $DefPageSize;        // default page size
//     protected $PageInfo;           // used for custom page sizes
//     protected $wPt, $hPt;          // dimensions of current page in points
//     protected $w, $h;              // dimensions of current page in user units
//     protected $lMargin;            // left margin
//     protected $tMargin;            // top margin
//     protected $rMargin;            // right margin
//     protected $bMargin;            // page break margin
//     protected $cMargin;            // cell margin
//     protected $x, $y;              // current position in user units
//     protected $lasth;              // height of last printed cell
//     protected $LineWidth;          // line width in user units
//     protected $fontpath;           // path containing fonts
//     protected $CoreFonts;          // array of core font names
//     protected $fonts;              // array of used fonts
//     protected $FontFiles;          // array of font files
//     protected $Encodings;          // array of encodings
//     protected $cmaps;              // array of ToUnicode CMap
//     protected $FontFamily;         // current font family
//     protected $FontStyle;          // current font style
//     protected $underline;          // underlining flag
//     protected $CurrentFont;        // current font info
//     protected $FontSizePt;         // current font size in points
//     protected $FontSize;           // current font size in user units
//     protected $TextColor;          // current text color
//     protected $FillColor;          // current fill color
//     protected $DrawColor;          // current drawing color
//     protected $ColorFlag;          // indicates whether fill and text colors are different
//     protected $WithAlpha;          // indicates whether alpha channel is used
//     protected $ws;                 // word spacing
//     protected $images;             // array of used images
//     protected $PageLinks;          // array of links in pages
//     protected $links;              // array of internal links
//     protected $AutoPageBreak;      // automatic page breaking
//     protected $PageBreakTrigger;   // threshold used to trigger page breaks
//     protected $InHeader;           // flag set when processing header
//     protected $InFooter;           // flag set when processing footer
//     protected $AliasNbPages;       // alias for total number of pages
//     protected $ZoomMode;           // zoom display mode
//     protected $LayoutMode;         // layout display mode
//     protected $metadata;           // document properties
//     protected $PDFVersion;         // PDF version number

//     function __construct($orientation='P', $unit='mm', $size='A4')
//     {
//         // Initialization of properties
//         $this->state = 0;
//         $this->page = 0;
//         $this->n = 2;
//         $this->buffer = '';
//         $this->pages = array();
//         $this->PageInfo = array();
//         $this->fonts = array();
//         $this->FontFiles = array();
//         $this->colors = array();
//         $this->images = array();
//         $this->links = array();
//         $this->InHeader = false;
//         $this->InFooter = false;
//         $this->lasth = 0;
//         $this->FontFamily = '';
//         $this->FontStyle = '';
//         $this->FontSizePt = 12;
//         $this->underline = false;
//         $this->DrawColor = '0 G';
//         $this->TextColor = '0 g';
//         $this->FillColor = '0 g';
//         $this->ColorFlag = false;
//         $this->WithAlpha = false;
//         $this->ws = 0;
//         // Scale factor
//         if($unit=='pt') $this->k = 1;
//         elseif($unit=='mm') $this->k = 72/25.4;
//         elseif($unit=='cm') $this->k = 72/2.54;
//         elseif($unit=='in') $this->k = 72;
//         else $this->Error('Incorrect unit: '.$unit);
//         // Page size and orientation
//         $this->DefOrientation = $orientation;
//         $this->DefPageSize = array(210, 297); // A4
//         $this->SetMargins(10, 10);
//         $this->cMargin = $this->lMargin/10;
//         $this->LineWidth = .567/$this->k;
//         $this->SetAutoPageBreak(true, 20);
//         $this->SetFont('Arial', '', 12);
//         $this->PDFVersion = '1.3';
//     }

//     function SetMargins($left, $top, $right=null) { $this->lMargin = $left; $this->tMargin = $top; $this->rMargin = ($right!==null) ? $right : $left; }
//     function SetFont($family, $style='', $size=0) {
//         $family = strtolower($family);
//         if($family=='arial') $family='helvetica';
//         $this->FontFamily = $family;
//         $this->FontStyle = strtoupper($style);
//         if($size>0) $this->FontSizePt = $size;
//         $this->FontSize = $this->FontSizePt/$this->k;
//     }
//     function AddPage($orientation='', $size='', $rotation=0) {
//         $this->page++;
//         $this->pages[$this->page] = '';
//         $this->state = 2;
//         $this->x = $this->lMargin;
//         $this->y = $this->tMargin;
//     }
//     function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') {
//         $this->pages[$this->page] .= sprintf('BT %.2F %.2F Td (%s) Tj ET ', $this->x*$this->k, ($this->h-$this->y)*$this->k, $txt);
//         if($ln==1) { $this->x = $this->lMargin; $this->y += $h; } else { $this->x += $w; }
//     }
//     function Output($dest='', $name='', $isUTF8=false) {
//         header('Content-Type: application/pdf');
//         echo "%PDF-1.3\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
//         // (Simplified for placeholder)
//         exit;
//     }
//     function Error($msg) { die('<b>FPDF error:</b> '.$msg); }
// }
// ?> -->