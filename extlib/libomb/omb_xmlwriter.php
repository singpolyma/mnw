<?php
class omb_XMLWriter extends XMLWriter {
  public function writeFullElement($tag, $attributes, $content) {
    $this->startElement($tag);
    if (!is_null($attributes)) {
      foreach ($attributes as $name => $value) {
        $this->writeAttribute($name, $value);
      }
    }
    if (is_array($content)) {
      foreach ($content as $values) {
        $this->writeFullElement($values[0], $values[1], $values[2]);
      }
    } else {
      $this->text($content);
    }
    $this->fullEndElement();
  }
}
?>
