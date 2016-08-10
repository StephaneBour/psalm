<?php

namespace Psalm;

class Context
{
    /** @var array<Type\Union> */
    public $vars_in_scope = [];

    public $vars_possibly_in_scope = [];

    /** @var boolean */
    public $in_loop = false;

    /** @var string|null */
    public $self;

    /** @var string|null */
    public $parent;

    public function __clone()
    {
        foreach ($this->vars_in_scope as $key => &$type) {
            $type = clone $type;
        }
    }

    /**
     * Updates the parent context, looking at the changes within a block
     * and then applying those changes, where necessary, to the parent context
     *
     * @param  Context $start_context
     * @param  Context $end_context
     * @param  bool    $has_leaving_statements   whether or not the parent scope is abandoned between $start_context and $end_context
     * @return void
     */
    public function update(Context $start_context, Context $end_context, $has_leaving_statements, array $vars_to_update, array &$updated_vars)
    {
        foreach ($this->vars_in_scope as $var => &$context_type) {
            $old_type = $start_context->vars_in_scope[$var];
            // if we're leaving, we're effectively deleting the possibility of the if types
            $new_type = !$has_leaving_statements ? $end_context->vars_in_scope[$var] : null;

            // this is only true if there was some sort of type negation
            if (in_array($var, $vars_to_update)) {

                // if the type changed within the block of statements, process the replacement
                if ((string)$old_type !== (string)$new_type) {
                    $context_type->substitute($old_type, $new_type);
                    $updated_vars[$var] = true;
                }
            }
        }
    }

    public static function getRedefinedVars(Context $original_context, Context $new_context)
    {
        $redefined_vars = [];

        foreach ($original_context->vars_in_scope as $var => $context_type) {
            if ((string)$new_context->vars_in_scope[$var] !== (string)$context_type) {
                $redefined_vars[$var] = $new_context->vars_in_scope[$var];
            }
        }

        return $redefined_vars;
    }
}