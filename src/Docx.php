<?php

namespace IRebega\DocxReplacer;

use IRebega\DocxReplacer\Exceptions\DocxException;

/**
 * Replace text to text or even text to images in DOCX files
 *
 * @author i.rebega <igorrebega@gmail.com>
 */
class Docx extends \ZipArchive
{
    // Use to change PX to EMU
    const PX_TO_EMU = 8625;
    const REL_LOCATION = 'word/_rels/document.xml.rels';
    const DOCUMENT_BODY_LOCATION = 'word/document.xml';
    const HEADER_LOCATION = 'word/header1.xml';
    const FOOTER_LOCATION = 'word/footer1.xml';

    /**
     * @var string
     */
    protected $path;

    /**
     * Docx constructor.
     * @param string $path path to DOCX file
     * @throws \Exception
     */
    public function __construct($path)
    {
        $this->path = $path;

        if ($this->open($path, \ZipArchive::CREATE) !== TRUE) {
            throw new DocxException("Unable to open <$path>");
        }
    }

    /**
     * Replace one text to another
     * @param $from
     * @param $to
     */
    public function replaceText($from, $to)
    {
        $this->replaceTextInLocation($from, $to, self::HEADER_LOCATION);
        $this->replaceTextInLocation($from, $to, self::FOOTER_LOCATION);
        $this->replaceTextInLocation($from, $to, self::DOCUMENT_BODY_LOCATION);
    }
    
    /**
     *
     * Replace many text to anothers, similar to replaceText but with array
     * @param $texts an associative array where $from is the key and $to the value
     */
    public function replaceTexts($texts)
    {
        $this->replaceTextsInLocation($texts, self::HEADER_LOCATION);
        $this->replaceTextsInLocation($texts, self::FOOTER_LOCATION);
        $this->replaceTextsInLocation($texts, self::DOCUMENT_BODY_LOCATION);
    }

    /**
     * Replace text to given image
     * @param string $text What text search
     * @param string $path Image to which we want to replace text
     * @throws \Exception
     */
    public function replaceTextToImage($text, $path)
    {
        if (!file_exists($path)) {
            throw new \Exception('Image not exists');
        };
        list($width, $height, $type) = getimagesize($path);
        $name = StringHelper::random(10) . $type;
        $zipPath = 'word/media/' . $name;
        $this->addFromString($zipPath, file_get_contents($path));

        $relId = $this->addRel('http://schemas.openxmlformats.org/officeDocument/2006/relationships/image', "media/$name");

        $block = $this->getImageBlock($relId, $width, $height);

        $this->replaceTextToBlock($text, $block);
    }

    /**
     * Replace one text to another in $location
     * @param $from
     * @param $to
     * @param $location
     */
    private function replaceTextInLocation($from, $to, $location)
    {
        $message = $this->getFromName($location);
        $message = str_replace($from, $to, $message);

        $this->addFromString($location, $message);

        $this->save();
    }
    
     /**
     * Replace many text to anothers 
     * @param $texts  an associative array where $from is the key and $to the value
     * @param $location
     */
    private function replaceTextsInLocation($texts, $location)
    {   
            $message = $this->getFromName($location);
            foreach($texts as $from => $to)
            {
                $message = str_replace($from, $to, $message);
            }
            $this->addFromString($location, $message);

            $this->save();        
    }


    /**
     * Save changes to archive
     */
    private function save()
    {
        $this->close();
        $this->open($this->path, \ZipArchive::CREATE);
    }

    /**
     * This block we use to insert into document xml
     *
     * @param $relId
     * @param $width
     * @param $height
     * @return mixed
     */
    private function getImageBlock($relId, $width, $height)
    {
        $block = file_get_contents(__DIR__ . '/../templates/image.xml');
        $block = str_replace('{RID}', $relId, $block);
        $block = str_replace('{WIDTH}', $width * self::PX_TO_EMU, $block);
        return str_replace('{HEIGHT}', $height * self::PX_TO_EMU, $block);
    }

    /**
     * Find block that have $text inside and replace that block to $customBlock
     *
     * @param $text
     * @param $block
     */
    private function replaceTextToBlock($text, $block)
    {
        $file = $this->getFromName(self::DOCUMENT_BODY_LOCATION);
        $file = (new ReplaceTextBlockToCustom($file))->result($text, $block);
        $this->addFromString(self::DOCUMENT_BODY_LOCATION, $file);
        $this->save();
    }

    /**
     * @param $type
     * @param $target
     * @return string RelId
     */
    private function addRel($type, $target)
    {
        $file = $this->getFromName(self::REL_LOCATION);
        $xml = new \SimpleXMLElement($file);

        $lastId = $this->getLastMaxRelId($xml);

        $child = $xml->addChild("Relationship");
        $child->addAttribute('Id', 'rId' . ($lastId + 1));
        $child->addAttribute('Type', $type);
        $child->addAttribute('Target', $target);

        $this->addFromString(self::REL_LOCATION, $xml->asXML());

        return 'rId' . ($lastId + 1);
    }

    /**
     * @param \SimpleXMLElement $xml
     * @return int
     */
    private function getLastMaxRelId(\SimpleXMLElement $xml)
    {
        $rel = $xml->Relationship[0]['Id'];
        $max = $this->getNumberFromRelId($rel);
        foreach ($xml->Relationship as $relationship) {
            $number = $this->getNumberFromRelId($relationship['Id']);
            if ($number > $max) {
                $max = $number;
            }
        }
        return $max;
    }

    /**
     * @param $relId
     * @return int
     */
    private function getNumberFromRelId($relId)
    {
        preg_match('!\d+!', $relId, $matches);
        return (int)$matches[0];
    }
}
