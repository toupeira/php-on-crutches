<?# $Id$ ?>
<?

   function toggle_link($title, $id=null) {
      if ($id) {
         $onclick = "\$('$id').toggle(); return false";
      } else {
         $onclick = '$(this.nextSibling).toggle(); return false';
      }
      return link_to($title, '#', array('onclick' => $onclick));
   }

?>
