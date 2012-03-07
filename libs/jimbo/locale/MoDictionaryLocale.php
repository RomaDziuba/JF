<?php 
require_once dirname(__FILE__)."/IDictionaryLocale.php";

class MoDictionaryLocale implements IDictionaryLocale
{
    protected $moFilePath;
    protected $dictionaries;
    
    public function __construct($file)
    {
        $this->dictionaries = array();
        $this->moFilePath = $file;
    } // end __construct
    
    public function load()
    {
        $reader = new POMO_FileReader($this->moFilePath);
        if (!$reader->is_resource()) {
            return false;
        }

        $endian_string = $this->get_byteorder($reader->readint32());
        if (false === $endian_string) {
            return false;
        }
        $reader->setEndian($endian_string);
        
        $endian = ('big' == $endian_string)? 'N' : 'V';
        
        $header = $reader->read(24);
        if ($reader->strlen($header) != 24)
            return false;
        
        // parse header
        $header = unpack("{$endian}revision/{$endian}total/{$endian}originals_lenghts_addr/{$endian}translations_lenghts_addr/{$endian}hash_length/{$endian}hash_addr", $header);
        if (!is_array($header))
            return false;
        
        extract( $header );
        
        // support revision 0 of MO format specs, only
        if ($revision != 0)
            return false;
        
        // seek to data blocks
        $reader->seekto($originals_lenghts_addr);
        
        // read originals' indices
        $originals_lengths_length = $translations_lenghts_addr - $originals_lenghts_addr;
        if ( $originals_lengths_length != $total * 8 )
            return false;
        
        $originals = $reader->read($originals_lengths_length);
        if ( $reader->strlen( $originals ) != $originals_lengths_length )
            return false;
        
        // read translations' indices
        $translations_lenghts_length = $hash_addr - $translations_lenghts_addr;
        if ( $translations_lenghts_length != $total * 8 )
            return false;
        
        $translations = $reader->read($translations_lenghts_length);
        if ( $reader->strlen( $translations ) != $translations_lenghts_length )
            return false;
        
        // transform raw data into set of indices
        $originals    = $reader->str_split( $originals, 8 );
        $translations = $reader->str_split( $translations, 8 );
        
        // skip hash table
        $strings_addr = $hash_addr + $hash_length * 4;
        
        $reader->seekto($strings_addr);
        
        $strings = $reader->read_all();
        $reader->close();
        
        for ( $i = 0; $i < $total; $i++ ) {
            $o = unpack( "{$endian}length/{$endian}pos", $originals[$i] );
            $t = unpack( "{$endian}length/{$endian}pos", $translations[$i] );
            if ( !$o || !$t ) return false;
        
            // adjust offset due to reading strings to separate space before
            $o['pos'] -= $strings_addr;
            $t['pos'] -= $strings_addr;
        
            $original    = $reader->substr( $strings, $o['pos'], $o['length'] );
            $translation = $reader->substr( $strings, $t['pos'], $t['length'] );
        
            
            if ($original != '') {
                $this->dictionaries[$original] = $translation;
            }
        }
        
        return true;
    }
    
    function get_byteorder($magic) 
    {
        // The magic is 0x950412de
    
        // bug in PHP 5.0.2, see https://savannah.nongnu.org/bugs/?func=detailitem&item_id=10565
        $magic_little = (int) - 1794895138;
        $magic_little_64 = (int) 2500072158;
        // 0xde120495
        $magic_big = ((int) - 569244523) & 0xFFFFFFFF;
        if ($magic_little == $magic || $magic_little_64 == $magic) {
            return 'little';
        } else if ($magic_big == $magic) {
            return 'big';
        } else {
            return false;
        }
    }
    
    public function get($key)
    {
        return isset($this->dictionaries[$key]) ? $this->dictionaries[$key] : false;
    } // end get
    
    public function getAll()
    {
        return $this->dictionaries;
    }
    
}

if ( !class_exists( 'POMO_Reader' ) ):
class POMO_Reader {

    var $endian = 'little';
    var $_post = '';

    function POMO_Reader() {
        $this->is_overloaded = ((ini_get("mbstring.func_overload") & 2) != 0) && function_exists('mb_substr');
        $this->_pos = 0;
    }

    /**
     * Sets the endianness of the file.
     *
     * @param $endian string 'big' or 'little'
     */
    function setEndian($endian) {
        $this->endian = $endian;
    }

    /**
     * Reads a 32bit Integer from the Stream
     *
     * @return mixed The integer, corresponding to the next 32 bits from
     * 	the stream of false if there are not enough bytes or on error
     */
    function readint32() {
        $bytes = $this->read(4);
        if (4 != $this->strlen($bytes))
            return false;
        $endian_letter = ('big' == $this->endian)? 'N' : 'V';
        $int = unpack($endian_letter, $bytes);
        return array_shift($int);
    }

    /**
     * Reads an array of 32-bit Integers from the Stream
     *
     * @param integer count How many elements should be read
     * @return mixed Array of integers or false if there isn't
     * 	enough data or on error
     */
    function readint32array($count) {
        $bytes = $this->read(4 * $count);
        if (4*$count != $this->strlen($bytes))
            return false;
        $endian_letter = ('big' == $this->endian)? 'N' : 'V';
        return unpack($endian_letter.$count, $bytes);
    }


    function substr($string, $start, $length) {
        if ($this->is_overloaded) {
            return mb_substr($string, $start, $length, 'ascii');
        } else {
            return substr($string, $start, $length);
        }
    }

    function strlen($string) {
        if ($this->is_overloaded) {
            return mb_strlen($string, 'ascii');
        } else {
            return strlen($string);
        }
    }

    function str_split($string, $chunk_size) {
        if (!function_exists('str_split')) {
            $length = $this->strlen($string);
            $out = array();
            for ($i = 0; $i < $length; $i += $chunk_size)
                $out[] = $this->substr($string, $i, $chunk_size);
            return $out;
        } else {
            return str_split( $string, $chunk_size );
        }
    }


    function pos() {
        return $this->_pos;
    }

    function is_resource() {
        return true;
    }

    function close() {
        return true;
    }
}
endif;

if ( !class_exists( 'POMO_FileReader' ) ):
class POMO_FileReader extends POMO_Reader {
    function POMO_FileReader($filename) {
        parent::POMO_Reader();
        $this->_f = fopen($filename, 'rb');
    }

    function read($bytes) {
        return fread($this->_f, $bytes);
    }

    function seekto($pos) {
        if ( -1 == fseek($this->_f, $pos, SEEK_SET)) {
            return false;
        }
        $this->_pos = $pos;
        return true;
    }

    function is_resource() {
        return is_resource($this->_f);
    }

    function feof() {
        return feof($this->_f);
    }

    function close() {
        return fclose($this->_f);
    }

    function read_all() {
        $all = '';
        while ( !$this->feof() )
            $all .= $this->read(4096);
        return $all;
    }
}
endif;




?>