<?php
class Socket{
  private \Socket $socket;

  private string $ip;

  private ?int $port;

  private int $type;

  public function __construct(string $ip, ?int $port = null){
    $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    $this->ip = $ip;
    $this->port = $port;
  }

  public function connect(): void{
    $this->type = 1;
    socket_connect($this->socket, $this->ip, $this->port);
  }

  public function create(int $backlog = SOMAXCONN): void{
    $this->type = 2;
    socket_bind($this->socket, $this->ip, $this->port);
    socket_listen($this->socket, $backlog);
  }

  public function write(string $data): void{
    socket_write($this->socket, $data);
  }

  public function read(int $length = 8192): string{
    return socket_read($this->socket, $length);
  }

  public function __destruct(){
    socket_close($this->socket);
  }
}