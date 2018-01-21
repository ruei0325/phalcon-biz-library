<?php

namespace Codeages\PhalconBiz\Event;

final class WebEvents
{
    /**
     * The REQUEST event occurs at the very beginning of request
     * dispatching.
     *
     * This event allows you to create a response for a request before any
     * other code in the framework is executed.
     *
     * @Event("Codeages\PhalconBiz\Event\GetResponseEvent")
     *
     * @var string
     */
    const REQUEST = 'web.request';

    /**
     * The EXCEPTION event occurs when an uncaught exception appears.
     *
     * This event allows you to create a response for a thrown exception or
     * to modify the thrown exception.
     *
     * @Event("Codeages\PhalconBiz\Event\GetResponseForExceptionEvent")
     *
     * @var string
     */
    const EXCEPTION = 'web.exception';

    /**
     * The VIEW event occurs when the return value of a controller
     * is not a Response instance.
     *
     * This event allows you to create a response for the return value of the
     * controller.
     *
     * @Event("Codeages\PhalconBiz\Event\GetResponseForControllerResultEvent")
     *
     * @var string
     */
    const VIEW = 'web.view';

    /**
     * The RESPONSE event occurs once a response was created for
     * replying to a request.
     *
     * This event allows you to modify or replace the response that will be
     * replied.
     *
     * @Event("Codeages\PhalconBiz\Event\FilterResponseEvent")
     *
     * @var string
     */
    const RESPONSE = 'web.response';
}
