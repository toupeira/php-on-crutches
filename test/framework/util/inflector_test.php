<?# $Id$ ?>
<?

   class InflectionsTest extends TestCase
   {
      function test_pluralize_plurals() {
         $this->assertEqual('plurals', pluralize('plurals'));
         $this->assertEqual('Plurals', pluralize('Plurals'));
      }

      function test_pluralize_empty_string() {
         $this->assertEqual('', pluralize(''));
      }

      function test_pluralize() {
         foreach (self::$_singular_to_plural as $singular => $plural) {
            $this->assertEqual($plural, pluralize($singular));
            $this->assertEqual(ucfirst($plural), pluralize(ucfirst($singular)));
         }
      }

      function test_singularize() {
         foreach (self::$_singular_to_plural as $singular => $plural) {
            $this->assertEqual($singular, singularize($plural));
            $this->assertEqual(ucfirst($singular), singularize(ucfirst($plural)));
         }
      }

      function test_titleize() {
         foreach (self::$_mixture_to_title as $before => $titleized) {
            $this->assertEqual($titleized, titleize($before));
         }
      }

      function test_underscore() {
         foreach (self::$_camel_to_underscore as $camel => $underscore) {
            $this->assertEqual($underscore, underscore($camel));
         }
      }

      function test_underscore_without_reverse() {
         foreach (self::$_camel_to_underscore_without_reverse as $camel => $underscore) {
            $this->assertEqual($underscore, underscore($camel));
         }
      }

      function test_camelize() {
         foreach (self::$_camel_to_underscore as $camel => $underscore) {
            $this->assertEqual($camel, camelize($underscore));
         }
      }

      function test_foreign_key() {
         foreach (self::$_class_to_foreign_key as $class => $foreign_key) {
            $this->assertEqual($foreign_key, foreign_key($class));
         }
      }

      function test_foreign_key_without_underscore() {
         foreach (self::$_class_to_foreign_key_without_underscore as $class => $foreign_key) {
            $this->assertEqual($foreign_key, foreign_key($class, null));
         }
      }

      function test_tableize() {
         foreach (self::$_class_to_table as $class => $table) {
            $this->assertEqual($table, tableize($class));
         }
      }

      function test_classify() {
         foreach (self::$_class_to_table as $class => $table) {
            $this->assertEqual($class, classify($table));
         }
      }

      function test_parameterize() {
         foreach (self::$_string_to_parameterized as $string => $parameterized) {
            $this->assertEqual($parameterized, parameterize($string));
         }
      }

      function test_parameterize_with_custom_separator() {
         foreach (self::$_string_to_parameterized as $string => $parameterized) {
            $this->assertEqual(str_replace('-', '_', $parameterized), parameterize($string, '_'));
         }
      }

      function test_humanize() {
         foreach (self::$_underscore_to_human as $underscore => $human) {
            $this->assertEqual($human, humanize($underscore));
         }
      }

      static protected $_singular_to_plural = array(
         "search" => "searches",
         "switch" => "switches",
         "fix" => "fixes",
         "box" => "boxes",
         "process" => "processes",
         "address" => "addresses",
         "case" => "cases",
         "stack" => "stacks",
         "wish" => "wishes",
         "fish" => "fish",
      
         "category" => "categories",
         "query" => "queries",
         "ability" => "abilities",
         "agency" => "agencies",
         "movie" => "movies",
      
         "archive" => "archives",
      
         "index" => "indices",
      
         "wife" => "wives",
         "safe" => "saves",
         "half" => "halves",
      
         "move" => "moves",
      
         "salesperson" => "salespeople",
         "person" => "people",
      
         "spokesman" => "spokesmen",
         "man" => "men",
         "woman" => "women",
      
         "basis" => "bases",
         "diagnosis" => "diagnoses",
         "diagnosis_a" => "diagnosis_as",
      
         "datum" => "data",
         "medium" => "media",
         "analysis" => "analyses",
      
         "node_child" => "node_children",
         "child" => "children",
      
         "experience" => "experiences",
         "day" => "days",
      
         "comment" => "comments",
         "foobar" => "foobars",
         "newsletter" => "newsletters",
      
         "old_news" => "old_news",
         "news" => "news",
      
         "series" => "series",
         "species" => "species",
      
         "quiz" => "quizzes",
      
         "perspective" => "perspectives",
      
         "ox" => "oxen",
         "photo" => "photos",
         "buffalo" => "buffaloes",
         "tomato" => "tomatoes",
         "dwarf" => "dwarves",
         "elf" => "elves",
         "information" => "information",
         "equipment" => "equipment",
         "bus" => "buses",
         "status" => "statuses",
         "status_code" => "status_codes",
         "mouse" => "mice",
      
         "louse" => "lice",
         "house" => "houses",
         "octopus" => "octopi",
         "virus" => "viri",
         "alias" => "aliases",
         "portfolio" => "portfolios",
      
         "vertex" => "vertices",
         "matrix" => "matrices",
         "matrix_fu" => "matrix_fus",
      
         "axis" => "axes",
         "testis" => "testes",
         "crisis" => "crises",
      
         "rice" => "rice",
         "shoe" => "shoes",
      
         "horse" => "horses",
         "prize" => "prizes",
         "edge" => "edges",
      
         "cow" => "kine",
      );

      static protected $_camel_to_underscore = array(
         "Product" => "product",
         "SpecialGuest" => "special_guest",
         "ApplicationController" => "application_controller",
         "Area51Controller" => "area51_controller",
      );

      static protected $_underscore_to_lower_camel = array(
         "product" => "product",
         "special_guest" => "specialGuest",
         "application_controller" => "applicationController",
         "area51_controller" => "area51Controller",
      );

      static protected $_camel_to_underscore_without_reverse = array(
         "HTMLTidy" => "html_tidy",
         "HTMLTidyGenerator" => "html_tidy_generator",
         "FreeBSD" => "free_bsd",
         "HTML" => "html",
      );

      static protected $_class_to_foreign_key = array(
         "Person" => "person_id",
         "Account" => "account_id",
      );

      static protected $_class_to_foreign_key_without_underscore = array(
         "Person" => "personid",
         "Account" => "accountid",
      );

      static protected $_class_to_table = array(
         "PrimarySpokesman" => "primary_spokesmen",
         "NodeChild" => "node_children",
      );

      static protected $_string_to_parameterized = array(
         "Donald E. Knuth" => "donald-e-knuth",
         "Random text with *(bad)* characters" => "random-text-with-bad-characters",
         "Malmö" => "malmo",
         "Garçons" => "garcons",
         "Allow_Under_Scores" => "allow_under_scores",
         "Trailing bad characters!@#" => "trailing-bad-characters",
         "!@#Leading bad characters" => "leading-bad-characters",
         "Squeeze separators" => "squeeze-separators",
      );

      static protected $_underscore_to_human = array(
         "employee_salary" => "Employee salary",
         "employee_id" => "Employee",
         "underground" => "Underground",
      );

      static protected $_mixture_to_title = array(
         "active_record" => "Active Record",
         "ActiveRecord" => "Active Record",
         "action web service" => "Action Web Service",
         "Action Web Service" => "Action Web Service",
         "Action web service" => "Action Web Service",
         "actionwebservice" => "Actionwebservice",
         "Actionwebservice" => "Actionwebservice",
         "david's code" => "David's Code",
         "David's code" => "David's Code",
         "david's Code" => "David's Code",
      );

      static protected $_ordinal_numbers = array(
         "0" => "0th",
         "1" => "1st",
         "2" => "2nd",
         "3" => "3rd",
         "4" => "4th",
         "5" => "5th",
         "6" => "6th",
         "7" => "7th",
         "8" => "8th",
         "9" => "9th",
         "10" => "10th",
         "11" => "11th",
         "12" => "12th",
         "13" => "13th",
         "14" => "14th",
         "20" => "20th",
         "21" => "21st",
         "22" => "22nd",
         "23" => "23rd",
         "24" => "24th",
         "100" => "100th",
         "101" => "101st",
         "102" => "102nd",
         "103" => "103rd",
         "104" => "104th",
         "110" => "110th",
         "111" => "111th",
         "112" => "112th",
         "113" => "113th",
         "1000" => "1000th",
         "1001" => "1001st",
      );

      static protected $_underscores_to_dashes = array(
         "street" => "street",
         "street_address" => "street-address",
         "person_street_address" => "person-street-address",
      );

      static protected $_irregularities = array(
         "person" => "people",
         "man" => "men",
         "child" => "children",
         "sex" => "sexes",
         "move" => "moves",
      );

   }

   # Fake classes for classify()
   class PrimarySpokesman {}
   class NodeChild {}

?>
