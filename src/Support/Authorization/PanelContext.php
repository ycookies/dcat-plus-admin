<?php

namespace Dcat\Admin\Support\Authorization;

use Closure;
use Dcat\Admin\Admin;
use Dcat\Admin\Application;
use InvalidArgumentException;

/**
 * Executes a read operation under a specific admin panel configuration.
 *
 * Multi-app route and model configuration is selected through Admin::app().
 * Keeping the temporary switch here avoids leaking the selected panel to the
 * remainder of the current request.
 */
class PanelContext
{
    /**
     * @return mixed
     */
    public function run(string $panel, Closure $callback)
    {
        $panel = trim($panel) ?: Application::DEFAULT;
        $application = Admin::app();
        $this->ensureEnabled($application, $panel);

        $current = $application->getName();
        if ($current === $panel) {
            return $callback();
        }

        $application->switch($panel);

        try {
            return $callback();
        } finally {
            $application->switch($current);
        }
    }

    protected function ensureEnabled(Application $application, string $panel): void
    {
        if ($panel === Application::DEFAULT) {
            return;
        }

        if (! array_key_exists($panel, $application->getEnabledApps())) {
            throw new InvalidArgumentException("The admin panel [{$panel}] is not enabled.");
        }
    }
}
