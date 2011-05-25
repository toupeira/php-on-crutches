<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Build pagination links for a QuerySet
   if (!function_exists('page_links')) {
      function page_links($query_set, $offset=5) {
         $current = $query_set->page;
         $pages = $query_set->pages;

         $start = max(1, $current - $offset);
         $end = min($pages, $current + $offset);
         if ($start == 1) $end = min($pages, 2 * $offset + 1);
         if ($end == $pages) $start = max(1, $pages - 2 * $offset);

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
            $links = page_link(1).($start > 2 ? "... " : " ").$links;
         }

         if ($end < $pages) {
            $links .= ($end < $pages - 1 ? " ..." : " ").page_link($pages);
         }

         return $links;
      }
   }

   # Build a link to a page
   if (!function_exists('page_link')) {
      function page_link($page, $title=null) {
         return link_to(any(h($title), $page), current_params(array('page' => $page)));
      }
   }

?>
