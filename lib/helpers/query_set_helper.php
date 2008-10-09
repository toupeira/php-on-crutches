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
      $current = $query_set->page;
      $pages = $query_set->pages;

      $start = max(1, $current - 5);
      $end = min($pages, $current + 5);
      if ($start == 1) $end = 11;
      if ($end == $pages) $start = $pages - 10;

      $links = array();
      for ($page = $start; $page <= $end; $page++) {
         $link = page_link($page);

         if ($page == $current) {
            $link = "<strong>$link</strong>";
         }

         $links[] = $link;
      }

      $links = implode(' ', $links);

      if ($start > 1) {
         $links = "... $links";
      }

      if ($end < $pages) {
         $links .= " ...";
      }

      return $links;
   }

?>
