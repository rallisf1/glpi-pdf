<?php
/**
 -------------------------------------------------------------------------
 LICENSE

 This file is part of PDF plugin for GLPI.

 PDF is free software: you can redistribute it and/or modify
 it under the terms of the GNU Affero General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 PDF is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU Affero General Public License for more details.

 You should have received a copy of the GNU Affero General Public License
 along with Reports. If not, see <http://www.gnu.org/licenses/>.

 @package   pdf
 @authors   Nelly Mahu-Lasson, Remi Collet
 @copyright Copyright (c) 2009-2022 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/


class PluginPdfChange extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Change());
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, Change $job) {
      $dbu = new DbUtils();

      $ID = $job->getField('id');
      if (!$job->can($ID, READ)) {
         return false;
      }

      if (count($_SESSION['glpiactiveentities'])>1) {
         $entity = Dropdown::getDropdownName("glpi_entities", $job->fields["entities_id"]);
      } else {
         $entity = '';
      }

      if (count($_SESSION['glpiactiveentities'])>1) {
         $location = Dropdown::getDropdownName("glpi_locations", $job->fields["locations_id"]);
      } else {
         $location = '';
      }

      $recipient_name='';
      if ($job->fields["users_id_recipient"]) {
         $recipient      = new User();
         $recipient->getFromDB($job->fields["users_id_recipient"]);
         $recipient_name = $recipient->getName();
      }

      $sla = $due = $commentsla = '';
      if ($job->fields['time_to_resolve']) {
         $due = "<b><i>".sprintf(__('%1$s: %2$s'), __('ETA')."</b></i>",
                                 Html::convDateTime($job->fields['time_to_resolve']));
      }

      $lastupdate = Html::convDateTime($job->fields["date_mod"]);
      if ($job->fields['users_id_lastupdater'] > 0) {
         $lastupdate = sprintf(__('%1$s από %2$s'), $lastupdate,
                               $dbu->getUserName($job->fields["users_id_lastupdater"]));
      }

      $status = '';
      if (in_array($job->fields["status"], $job->getSolvedStatusArray())
            || in_array($job->fields["status"], $job->getClosedStatusArray())) {
         $status = sprintf(__('%1$s %2$s'), '-', Html::convDateTime($job->fields["solvedate"]));
      }
      if (in_array($job->fields["status"], $job->getClosedStatusArray())) {
         $status = sprintf(__('%1$s %2$s'), '-', Html::convDateTime($job->fields["closedate"]));
      }

      if ($job->fields["status"] == Change::WAITING) {
         $status = sprintf(__('%1$s %2$s'), '-',
               Html::convDateTime($job->fields['begin_waiting_date']));
      }

      // Observer
      $users     = [];
      $observers = '';
      foreach ($job->getUsers(CommonITILActor::OBSERVER) as $d) {
         if ($d['users_id']) {
            $tmp = Toolbox::stripTags($dbu->getUserName($d['users_id']));
            if ($d['alternative_email']) {
               $tmp .= ' ('.$d['alternative_email'].')';
            }
         } else {
            $tmp = $d['alternative_email'];
         }
         $users[] = $tmp;
      }
      if (count($users)) {
         $observers = implode(', ', $users);
      }

      // Assign to
      $users     = [];
      $technicians = '';
      foreach ($job->getUsers(CommonITILActor::ASSIGN) as $d) {
         if ($d['users_id']) {
            $tmp = Toolbox::stripTags($dbu->getUserName($d['users_id']));
            if ($d['alternative_email']) {
               $tmp .= ' ('.$d['alternative_email'].')';
            }
         } else {
            $tmp = $d['alternative_email'];
         }
         $users[] = $tmp;
      }
      if (count($users)) {
         $technicians = implode(', ', $users);
      }

      $pdf->setColumnsSize(100);

      $pdf->displayTitle("<b>".$job->fields["name"]."</b>");

      $pdf->setColumnsSize(50,50);

      $pdf->displayLine(
         "<b><i>".sprintf(__('%1$s: %2$s'), __('Πελάτης')."</i></b>", $entity),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Τοποθεσία')."</i></b>", $location));

      $pdf->setColumnsSize(33.3,33.3,33.3);

      $pdf->displayLine(
         "<b><i>".sprintf(__('%1$s: %2$s'), __('Ημερομηνία Αναφοράς')."</i></b>", Html::convDateTime($job->fields["date"])),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Εντολέας')."</i></b>", $recipient_name),
         "<b><i>".sprintf(__('%1$s: %2$s'), __('Επείγον')."</i></b>",
                          Toolbox::stripTags($job->getUrgencyName($job->fields["urgency"]))));

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Ενημερώθηκε').'</i></b>', $lastupdate),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Υπεύθυνος')."</i></b>", $observers),
         "<b><i>". sprintf(__('%1$s: %2$s'), __('Επίπτωση')."</i></b>",
                          Toolbox::stripTags($job->getImpactName($job->fields["impact"]))));

      $pdf->displayLine(
         "<b><i>".sprintf(__('%1$s: %2$s'), __('Κατάσταση')."</i></b>",
                          Toolbox::stripTags($job->getStatus($job->fields["status"])). $status),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Τεχνικός')."</i></b>", $technicians),
         "<b><i>".sprintf(__('%1$s: %2$s'), __('Προτεραιότητα')."</i></b>",
                           Toolbox::stripTags($job->getPriorityName($job->fields["priority"]))));

      $pdf->displayLine(
         $due,
         "<b><i>".sprintf(__('%1$s: %2$s'), __('Συνολική Διάρκεια')."</i></b>",
                          Toolbox::stripTags(CommonITILObject::getActionTime($job->fields["actiontime"]))),
         "<b><i>".sprintf(__('%1$s: %2$s'), __('Κατηγορία')."</i></b>",
                          Dropdown::getDropdownName("glpi_itilcategories",
                                                   $job->fields["itilcategories_id"])));

      $pdf->setColumnsSize(100);

      $pdf->displayText("<b><i>".sprintf(__('%1$s: %2$s')."</i></b>", __('Περιγραφή'), ''),
                                         Toolbox::stripTags($job->fields["content"]), 1);
      $pdf->displaySpace();
   }


   static function pdfAnalysis(PluginPdfSimplePDF $pdf, Change $job) {

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>".__('Διάγνωση')."</b>");

      $pdf->setColumnsSize(10, 90);

      $pdf->displayText(sprintf(__('%1$s: %2$s'), "<b><i>".__('Impacts')."</i></b>",
                                $job->fields['impactcontent']));

      $pdf->displayText(sprintf(__('%1$s: %2$s'), "<b><i>".__('Control list')."</i></b>",
                                $job->fields['controlistcontent']));
   }


   static function pdfPlan(PluginPdfSimplePDF $pdf, Change $job) {

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>".__('Ενέργειες')."</b>");

      $pdf->setColumnsSize(10, 90);

      $pdf->displayText(sprintf(__('%1$s: %2$s'), "<b><i>".__('Deployment plan')."</i></b>",
                                $job->fields['rolloutplancontent']));

      $pdf->displayText(sprintf(__('%1$s: %2$s'), "<b><i>".__('Backup plan')."</i></b>",
                                $job->fields['backoutplancontent']));

      $pdf->displayText(sprintf(__('%1$s: %2$s'), "<b><i>".__('Checklist')."</i></b>",
                                $job->fields['checklistcontent']));
   }


   static function pdfStat(PluginPdfSimplePDF $pdf, Change $job) {

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>".__('Statistics')."</b>");

      $pdf->displayTitle("<b>"._n('Date', 'Dates', 2)."</b>");

      $pdf->setColumnsSize(50, 50);
      $pdf->displayLine(__('Opening date'), Html::convDateTime($job->fields['date']));
      $pdf->displayLine(__('Time to resolve'), Html::convDateTime($job->fields['time_to_resolve']));

      if (in_array($job->fields["status"], $job->getSolvedStatusArray())
          || in_array($job->fields["status"], $job->getClosedStatusArray())) {
         $pdf->displayLine(__('Resolution date'), Html::convDateTime($job->fields['solvedate']));
      }
      if (in_array($job->fields["status"], $job->getClosedStatusArray())) {
         $pdf->displayLine(__('Closing date'), Html::convDateTime($job->fields['closedate']));
      }

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>"._n('Time', 'Times', 2)."</b>");

      $pdf->setColumnsSize(50, 50);
      if (isset($job->fields['takeintoaccount_delay_stat']) > 0) {
         if ($job->fields['takeintoaccount_delay_stat'] > 0) {
            $accountdelay = Toolbox::stripTags(Html::timestampToString($job->fields['takeintoaccount_delay_stat'],0));
         }
         $pdf->displayLine(__('Take into account'),
                           isset($accountdelay) ? $accountdelay : '');
      }

      if (in_array($job->fields["status"], $job->getSolvedStatusArray())
          || in_array($job->fields["status"], $job->getClosedStatusArray())) {
               if ($job->fields['solve_delay_stat'] > 0) {
            $pdf->displayLine(__('Resolution'),
                              Toolbox::stripTags(Html::timestampToString($job->fields['solve_delay_stat'],0)));
         }
      }
      if (in_array($job->fields["status"], $job->getClosedStatusArray())) {
         if ($job->fields['close_delay_stat'] > 0) {
            $pdf->displayLine(__('Closing'),
                              Toolbox::stripTags(Html::timestampToString($job->fields['close_delay_stat'],0)));
         }
      }
      if ($job->fields['waiting_duration'] > 0) {
         $pdf->displayLine(__('Pending'),
                           Toolbox::stripTags(Html::timestampToString($job->fields['waiting_duration'],0)));
      }

      $pdf->displaySpace();
   }


   function defineAllTabsPDF($options=[]) {

      $onglets = parent::defineAllTabsPDF($options);
      unset($onglets['Itil_Project$1']);
      unset($onglets['Impact$1']);

      if (Session::haveRight('change', Change::READALL) // for technician
            || Session::haveRight('followup', ITILFollowup::SEEPRIVATE)
            || Session::haveRight('task', TicketTask::SEEPRIVATE)) {
         $onglets['_private_'] = __('Private');
      }
      return $onglets;
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      $private = isset($_REQUEST['item']['_private_']);

      switch ($tab) {
         case '_private_' :
            // nothing to export, just a flag
            break;

         case 'Change$main' :
            self::pdfMain($pdf, $item);
            PluginPdfChange_Problem::pdfForChange($pdf, $item);
            PluginPdfChange_Ticket::pdfForChange($pdf, $item);
            self::pdfAnalysis($pdf, $item);
            self::pdfPlan($pdf, $item);
            PluginPdfChange_Item::pdfForChange($pdf, $item);
            PluginPdfCommonItilCost::pdfForItem($pdf, $item);
            PluginPdfChangeValidation::pdfForChange($pdf, $item);
            PluginPdfItilFollowup::pdfForItem($pdf, $item, $private);
            PluginPdfChangeTask::pdfForChange($pdf, $item, $private);
            if (Session::haveRight('document', READ)) {
               PluginPdfDocument::pdfForItem($pdf, $item);
            }
            PluginPdfITILSolution::pdfForItem($pdf, $item);     
            break;

         case 'Change$1' :
            //self::pdfAnalysis($pdf, $item);
            break;

         case 'Change$3' :
            //self::pdfPlan($pdf, $item);
            break;

         case 'Change$4' :
            //self::pdfStat($pdf, $item);
            break;

         case 'Change$5' :
            //PluginPdfItilFollowup::pdfForItem($pdf, $item,  $private);
            //PluginPdfChangeTask::pdfForChange($pdf, $item,  $private);
            //if (Session::haveRight('document', READ)) {
            //   PluginPdfDocument::pdfForItem($pdf, $item);
            //}
            //PluginPdfITILSolution::pdfForItem($pdf, $item);
            break;

         case 'ChangeValidation$1' :
            //PluginPdfChangeValidation::pdfForChange($pdf, $item);
            break;

         case 'ChangeCost$1' :
            // PluginPdfCommonItilCost::pdfForItem($pdf, $item);
            break;

         case 'Change_Problem$1' :
            //PluginPdfChange_Problem::pdfForChange($pdf, $item);
            break;

         case 'Change_Ticket$1' :
            //PluginPdfChange_Ticket::pdfForChange($pdf, $item);
            break;

         case 'Change_Item$1' :
            //PluginPdfChange_Item::pdfForChange($pdf, $item);
            break;

         default :
            return false;
      }

      return true;
   }


}

