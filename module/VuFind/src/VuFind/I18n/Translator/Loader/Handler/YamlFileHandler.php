<?php
namespace VuFind\I18n\Translator\Loader\Handler;

use Laminas\I18n\Translator\TextDomain;
use Symfony\Component\Yaml\Yaml as Parser;
use VuFind\I18n\Translator\Loader\Handler\Action\ActionInterface;
use VuFind\I18n\Translator\Loader\Handler\Action\FileAction;

class YamlFileHandler implements HandlerInterface
{
    use HandlerTrait;

    public function canHandle(ActionInterface $action): bool
    {
        return $action instanceof FileAction
            && in_array(pathinfo($action->getFile(), PATHINFO_EXTENSION), ['yml', 'yaml']);
    }

    protected function doHandle(FileAction $action): \Generator
    {
        yield $file = $action->getFile() => new TextDomain(Parser::parseFile($file) ?? []);
    }
}
