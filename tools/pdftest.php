<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 pdf - Export to PDF plugin for GLPI
 Copyright (C) 2003-2013 by the pdf Development Team.

 https://forge.indepnet.net/projects/pdf
 -------------------------------------------------------------------------

 LICENSE

 This file is part of pdf.

 pdf is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 pdf is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with pdf. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
*/

chdir(__DIR__);
require('../../../inc/includes.php');

require('../../../lib/ezpdf/class.ezpdf.php');
require('../inc/simplepdf.class.php');

$pdf = new PluginPdfSimplePDF();
$pdf->setHeader("PDF test header");
$pdf->newPage();

$pdf->setColumnsSize(100);
$pdf->displayTitle("PDF test title");
$pdf->setColumnsSize(60,20,20);
$pdf->displayLine("<b>PDF <i>test</i></b> line", "one", "two");
$pdf->displayText("<b>Comment:</b>", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut fringilla id ante id interdum. Morbi facilisis et lacus sit amet blandit. Nam ligula erat, euismod eget condimentum in, semper eget tellus. Cras vitae lacus fermentum, vestibulum eros sed, luctus massa. Vivamus commodo sodales interdum. Cras accumsan, nunc sit amet facilisis hendrerit, sem tellus gravida enim, ut facilisis tellus augue at dui. Morbi egestas nisi placerat nunc tempus mattis. ");
$pdf->displayLink('http://www.glpi-project.org/', 'http://www.glpi-project.org/');
$pdf->displaySpace();

$pdf->setColumnsSize(100);
$pdf->displayTitle("Alignment");
$pdf->setColumnsSize(40,20,40);
$pdf->setColumnsAlign('right', 'center', 'left');
$pdf->displayLine('right', 'center', 'left');
$pdf->displaySpace();

$pdf->setColumnsSize(100);
$pdf->displayTitle("End of Page 1");

$pdf->newPage();
$pdf->displayTitle("Page 2");

if (file_put_contents('pdftest.pdf', $pdf->output())) {
   echo "pdftest.pdf saved\n";
}
