<?php

class MessageManager
{
    const Message = 'Message';
    const Notice = 'Notice';
    const Error = 'Error';

    protected $type;
    protected $messages;

    public function __construct($type = self::Message) {
        $this->type = $type;
        $this->messages = array();
    }

    public function add($message) {
        $this->messages[] = $message;
    }

    private function color() {
        switch($this->type) {
            case self::Message:
                return 'green';
                break;
            case self::Notice:
                return 'blue';
                break;
            case self::Error:
                return 'red';
                break;
            default:
                return '';
        }
    }

    public function render() {
        $color = $this->color();
        if($this->messages) {
            echo("<div class=\"section\">\n");
            foreach($this->messages as $message):
                echo <<< HTML
                    <div class="message $color">
                        <span>$message</span>
                    </div>
HTML;
            endforeach;
            echo("</div>\n");
        }
    }

    public function hasMessages() {
        return count($this->messages) > 0;
    }

    public function getMessages() {
        return array_merge(array(), $this->messages);
    }
}

?>
