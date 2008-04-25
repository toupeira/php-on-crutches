<?# $Id$ ?>
<?

   function toggle_link($title) {
      return link_to($title, '#', array('onclick' => '$(this.nextSibling).toggle(); return false'));
   }

?>
