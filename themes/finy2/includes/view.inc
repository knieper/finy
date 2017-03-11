<?php
/**
 * @file
 * Theme and preprocess functions for views
 */
 /**
  * Implements template_preprocess_views_view_table().
  */
 function finy2_preprocess_views_view_table(&$variables) {
   // Add Bootstrap table classes.
   /* due to error message either need to set array first or comment this out...classes are also set in template so....srk 3-10-17
   $variables['classes_array'] = array_merge($variables['classes_array'], array(
     'table',
     'table-striped',
     'table-bordered',
   ));*/
 }

 //adds a number to each row in the view so that each theme can be themed differently
 function finy2_preprocess_views_view_unformatted(&$variables) {
     $view = $variables['view'];
     if($view->id() == 'events'){
       $rows = $variables['rows'];
   	   foreach ($rows as $id => $row) {
           $row_number = $id +1;
           $variables['rows'][$id]['attributes']->addClass('row-'.$row_number);
           if ($row_number % 2 == 0) {
             $variables['rows'][$id]['attributes']->addClass('even');
           }
           else {
             $variables['rows'][$id]['attributes']->addClass('odd');
             }
         }
       }
 }
