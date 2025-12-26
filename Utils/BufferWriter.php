<?php
declare(strict_types=1);

namespace Utils;

use OutOfBoundsException;
use Stringable;

class BufferWriter implements Stringable{
  private string $buffer;

  private readonly int $size;

  private int $offset = 0;

  public function __construct(int $size){
    $this->size = $size;
    $this->buffer = str_repeat("\0", $size);
  }

  public function setU8(int $value, ?int $offset = null): self{
    if(\is_int($offset) && $offset >= 0 && $offset < $this->size){
      $this->buffer[$offset] = \chr($value & 0xFF);
    }
    else if($this->offset + 1 <= $this->size){
      $this->buffer[$this->offset++] = \chr($value & 0xFF);
    }
    else{
      throw new OutOfBoundsException('Buffer overflow');
    }

    return $this;
  }

  public function setU16(int $value, ?int $offset = null): self{
    return $this
      ->setU8(($value >> 8) & 0xFF, $offset !== null ? $offset : null)
      ->setU8($value & 0xFF, $offset !== null ? $offset + 1 : null);
  }

  public function setU32(int $value, ?int $offset = null): self{
    return $this
      ->setU8(($value >> 24) & 0xFF, $offset !== null ? $offset : null)
      ->setU8(($value >> 16) & 0xFF, $offset !== null ? $offset + 1 : null)
      ->setU8(($value >> 8) & 0xFF, $offset !== null ? $offset + 2 : null)
      ->setU8($value & 0xFF, $offset !== null ? $offset + 3 : null);
  }

  public function setU64(int $value, ?int $offset = null): self{
    return $this
      ->setU32(($value >> 32) & 0xFFFFFFFF, $offset !== null ? $offset : null)
      ->setU32($value & 0xFFFFFFFF, $offset !== null ? $offset + 4 : null);
  }

  public function write(string $data, ?int $offset = null): self{
    $len = \strlen($data);

    if(\is_int($offset) && $offset >= 0 && $offset + $len <= $this->size){
      for($i = 0; $i < $len; $i++){
        $this->buffer[$offset + $i] = $data[$i];
      }
    }
    else if($this->offset + $len <= $this->size){
      for($i = 0; $i < $len; $i++){
        $this->buffer[$this->offset++] = $data[$i];
      }
    }
    else{
      throw new OutOfBoundsException('Buffer overflow');
    }

    return $this;
  }

  public function __tostring(): string{
    return $this->buffer;
  }
}