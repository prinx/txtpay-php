<?php

class Test
{
    public function run(Closure $callback)
    {
        $callback = $callback->bindTo($this);
        call_user_func($callback);
    }

    public function message()
    {
        return 'Yes ooo';
    }
}

$t = new Test;

$t->run(function () {
    echo $this->message();
});

