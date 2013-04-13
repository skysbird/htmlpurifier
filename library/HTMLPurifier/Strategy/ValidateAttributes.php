<?php

/**
 * Validate all attributes in the tokens.
 */

class HTMLPurifier_Strategy_ValidateAttributes extends HTMLPurifier_Strategy
{

    public function execute($tokens, $config, $context) {

        // setup validator
        $validator = new HTMLPurifier_AttrValidator();

        $token = false;
        $parent_token = false;
        $context->register('CurrentToken', $token);
        $context->register('ParentToken', $parent_token);

        foreach ($tokens as $key => $token) {

            // only process tokens that have attributes,
            //   namely start and empty tags
            if (!$token instanceof HTMLPurifier_Token_Start && !$token instanceof HTMLPurifier_Token_Empty) {
                    if($token instanceof HTMLPurifier_Token_End){
                        $parent_token = false;
                    }
                continue;
            }

            // skip tokens that are armored
            if (!empty($token->armor['ValidateAttributes'])) continue;

            // note that we have no facilities here for removing tokens
            $validator->validateToken($token, $config, $context);

            $parent_token  = $token;
            $tokens[$key] = $token; // for PHP 4
        }
        $context->destroy('CurrentToken');
        $context->destroy('ParentToken');

        return $tokens;
    }

}

// vim: et sw=4 sts=4
