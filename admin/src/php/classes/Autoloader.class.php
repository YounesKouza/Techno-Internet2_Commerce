<?php
class Autoloader{

    static function register(){
        spl_autoload_register(['Autoloader', 'autoload']); //ou __CLASS__ à la place de 'Autoloader'
        /*--> dans le cas d'une méthode statique, la syntaxe de spl_autoload_register attend un tableau
        dont le premier élément est la classe et le second la méthode
        */
    }

    public static function autoload($class){
        require_once $class.'.class.php';
    }
}
