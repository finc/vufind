<?php
namespace VuFind\I18n\Translator\Loader\Handler;

use Laminas\I18n\Translator\TextDomain;
use VuFind\I18n\Translator\Loader\Handler\Action\ActionInterface;
use VuFind\I18n\Translator\Loader\Handler\Action\FileAction;

class IniFileHandler implements HandlerInterface
{
    use HandlerTrait;

    public function canHandle(ActionInterface $action): bool
    {
        return $action instanceof FileAction
            && pathinfo($action->getFile(), PATHINFO_EXTENSION) === 'ini';
    }

    protected function doHandle(FileAction $action): \Generator
    {
        yield $file = $action->getFile() => $this->getTextDomain($file);
    }

    /**
     * Parse a language file.
     *
     * @param string|array $input Either a filename to read (passed as a
     * string) or a set of data to convert into a TextDomain (passed as an array)
     * @param bool $convertBlanks Should we convert blank strings to
     * zero-width non-joiners?
     *
     * @return TextDomain
     */
    public function getTextDomain($input, $convertBlanks = true)
    {
        $data = new TextDomain();

        // Manually parse the language file:
        $contents = is_array($input) ? $input : file($input);
        if (is_array($contents)) {
            foreach ($contents as $current) {
                // Split the string on the equals sign, keeping a max of two chunks:
                $parts = explode('=', $current, 2);
                $key = trim($parts[0]);
                if ($key != "" && substr($key, 0, 1) != ';') {
                    // Trim outermost double quotes off the value if present:
                    if (isset($parts[1])) {
                        $value = preg_replace(
                            '/^\"?(.*?)\"?$/', '$1', trim($parts[1])
                        );

                        // Store the key/value pair (allow empty values -- sometimes
                        // we want to replace a language token with a blank string,
                        // but Laminas translator doesn't support them so replace
                        // with a zero-width non-joiner):
                        if ($convertBlanks && $value === '') {
                            $value = html_entity_decode(
                                '&#x200C;', ENT_NOQUOTES, 'UTF-8'
                            );
                        }
                        $data[$key] = $value;
                    }
                }
            }
        }

        return $data;
    }
}
