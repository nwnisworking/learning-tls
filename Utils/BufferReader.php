<?php
declare(strict_types=1);

namespace Utils;

use OutOfBoundsException;

class BufferReader{
  private int $offset = 0;

  private readonly int $size;

  public function __construct(private readonly string $buffer){
    $this->size = strlen($buffer);
  }

  public function getU8(?int $offset = null): int{
    if(\is_int($offset) && $offset >= 0 && $offset + 1 <= $this->size){
      return \ord($this->buffer[$offset]);
    }
    else if($this->offset + 1 <= $this->size){
      return \ord($this->buffer[$this->offset++]);
    }
    else{
      throw new OutOfBoundsException('Buffer overflow');
    }
  }

  public function getU16(?int $offset = null): int{
    return ($this->getU8($offset) << 8) | $this->getU8(\is_int($offset) ? $offset + 1 : null);
  }

  public function getU32(?int $offset = null): int{
    return ($this->getU8($offset) << 24) |
           ($this->getU8(\is_int($offset) ? $offset + 1 : null) << 16) |
           ($this->getU8(\is_int($offset) ? $offset + 2 : null) << 8) |
           $this->getU8(\is_int($offset) ? $offset + 3 : null);
  }

  public function getU64(?int $offset = null): int{
    return ($this->getU32($offset) << 32) | $this->getU32(\is_int($offset) ? $offset + 4 : null);
  }

  public function read(int $length, ?int $offset = null): string{
    if(\is_int($offset) && $offset >= 0 && $offset + $length <= $this->size){
      return substr($this->buffer, $offset, $length);
    }
    else if($this->offset + $length <= $this->size){
      $data = substr($this->buffer, $this->offset, $length);
      $this->offset += $length;

      return $data;
    }
    else{
      throw new OutOfBoundsException('Buffer overflow');
    }
  }
}