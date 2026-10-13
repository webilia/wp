<?php
// Analysis-only declarations for optional UI APIs; never loaded at runtime.
namespace Webilia\UI;
class Html { public static function escape($value): string {} }
class AnnouncementCenter { public static function render(array $props): string {} }
class DeactivationFeedbackDialog { public static function render(array $props): string {} }
namespace Webilia\UI\WordPress;
class Assets { public static function enqueue(array $components, string $packageUrl): void {} }
