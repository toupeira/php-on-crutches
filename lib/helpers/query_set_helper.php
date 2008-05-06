<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function page_link($page, $title=null) {
      return link_to(any(h($title), $page), current_params(array('page' => $page)));
   }

   function page_links($query_set) {
      $links = array();
      for ($page = 1; $page <= $query_set->pages; $page++) {
         if ($query_set->page == $page) {
            $links[] = "<strong>$page</strong>";
         } else {
            $links[] = page_link($page);
         }
      }

      return implode(' ', $links);
   }

?>
