<?php declare(strict_types = 1);

// odsl-D:\returns-system\tests\Feature\Returns\PathCoverageTest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Tests\Feature\Returns\PathCoverageTest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3.30-e04e649a8dfe4513f826592f4de5b03bb130e8593661098b23ec03a10b35071a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'filename' => 'D:/returns-system/tests/Feature/Returns/PathCoverageTest.php',
      ),
    ),
    'namespace' => 'Tests\\Feature\\Returns',
    'name' => 'Tests\\Feature\\Returns\\PathCoverageTest',
    'shortName' => 'PathCoverageTest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Path matrix for the implemented return-ticket lifecycle.
 *
 * Decision nodes:
 * P1: admin middleware authenticated? yes/no.
 * P2: authenticated role allowed? admin/support/other.
 * P3: status payload valid? yes/no.
 * P4: rejected or more-information status has a comment? yes/no.
 * P5: closing requested by an admin? yes/no.
 * P6: transactional transition succeeds and records history.
 *
 * Independent executable paths:
 * 1. Unauthenticated -> redirect to login.
 * 2. Invalid administrative role -> 403.
 * 3. Valid admin/support request -> transition and history.
 * 4. Rejected without comment -> validation failure, no transition.
 * 5. More-information request without comment -> validation failure, no transition.
 * 6. Support closing -> 403, no transition.
 * 7. Admin closing -> transition and history.
 * 8. Invalid status -> validation failure, no transition.
 *
 * The repository currently has no public inspection, credit-note, or inventory
 * action. Those lifecycle segments cannot be covered without inventing a contract.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 37,
    'endLine' => 218,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Tests\\TestCase',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'test_guest_path_stops_at_admin_authentication_guard' => 
      array (
        'name' => 'test_guest_path_stops_at_admin_authentication_guard',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 41,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Tests\\Feature\\Returns',
        'declaringClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'implementingClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'currentClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'aliasName' => NULL,
      ),
      'test_non_administrative_role_stops_at_role_guard' => 
      array (
        'name' => 'test_non_administrative_role_stops_at_role_guard',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 55,
        'endLine' => 71,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Tests\\Feature\\Returns',
        'declaringClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'implementingClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'currentClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'aliasName' => NULL,
      ),
      'test_allowed_transition_updates_ticket_and_records_history' => 
      array (
        'name' => 'test_allowed_transition_updates_ticket_and_records_history',
        'parameters' => 
        array (
          'role' => 
          array (
            'name' => 'role',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 75,
            'endLine' => 75,
            'startColumn' => 9,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'newStatus' => 
          array (
            'name' => 'newStatus',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 76,
            'endLine' => 76,
            'startColumn' => 9,
            'endColumn' => 25,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'comment' => 
          array (
            'name' => 'comment',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 77,
            'endLine' => 77,
            'startColumn' => 9,
            'endColumn' => 24,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'PHPUnit\\Framework\\Attributes\\DataProvider',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'successfulTransitionProvider\'',
                'attributes' => 
                array (
                  'startLine' => 73,
                  'endLine' => 73,
                  'startTokenPos' => 256,
                  'startFilePos' => 2359,
                  'endTokenPos' => 256,
                  'endFilePos' => 2388,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 73,
        'endLine' => 99,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Tests\\Feature\\Returns',
        'declaringClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'implementingClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'currentClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'aliasName' => NULL,
      ),
      'successfulTransitionProvider' => 
      array (
        'name' => 'successfulTransitionProvider',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return array<string, array{string, string, ?string}>
 */',
        'startLine' => 104,
        'endLine' => 112,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Tests\\Feature\\Returns',
        'declaringClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'implementingClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'currentClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'aliasName' => NULL,
      ),
      'test_comment_required_guard_keeps_ticket_unchanged' => 
      array (
        'name' => 'test_comment_required_guard_keeps_ticket_unchanged',
        'parameters' => 
        array (
          'newStatus' => 
          array (
            'name' => 'newStatus',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 115,
            'endLine' => 115,
            'startColumn' => 72,
            'endColumn' => 88,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'PHPUnit\\Framework\\Attributes\\DataProvider',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'commentRequiredStatusProvider\'',
                'attributes' => 
                array (
                  'startLine' => 114,
                  'endLine' => 114,
                  'startTokenPos' => 537,
                  'startFilePos' => 3861,
                  'endTokenPos' => 537,
                  'endFilePos' => 3891,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 114,
        'endLine' => 131,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Tests\\Feature\\Returns',
        'declaringClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'implementingClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'currentClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'aliasName' => NULL,
      ),
      'commentRequiredStatusProvider' => 
      array (
        'name' => 'commentRequiredStatusProvider',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return array<string, array{string}>
 */',
        'startLine' => 136,
        'endLine' => 142,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Tests\\Feature\\Returns',
        'declaringClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'implementingClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'currentClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'aliasName' => NULL,
      ),
      'test_support_cannot_close_ticket' => 
      array (
        'name' => 'test_support_cannot_close_ticket',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 144,
        'endLine' => 161,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Tests\\Feature\\Returns',
        'declaringClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'implementingClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'currentClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'aliasName' => NULL,
      ),
      'test_admin_can_close_ticket_after_validating_the_guard' => 
      array (
        'name' => 'test_admin_can_close_ticket_after_validating_the_guard',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 163,
        'endLine' => 184,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Tests\\Feature\\Returns',
        'declaringClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'implementingClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'currentClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'aliasName' => NULL,
      ),
      'test_invalid_status_stops_at_request_validation' => 
      array (
        'name' => 'test_invalid_status_stops_at_request_validation',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 186,
        'endLine' => 202,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Tests\\Feature\\Returns',
        'declaringClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'implementingClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'currentClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'aliasName' => NULL,
      ),
      'createTicket' => 
      array (
        'name' => 'createTicket',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Models\\ReturnTicket',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 204,
        'endLine' => 212,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Tests\\Feature\\Returns',
        'declaringClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'implementingClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'currentClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'aliasName' => NULL,
      ),
      'statusUrl' => 
      array (
        'name' => 'statusUrl',
        'parameters' => 
        array (
          'ticket' => 
          array (
            'name' => 'ticket',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\ReturnTicket',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 214,
            'endLine' => 214,
            'startColumn' => 32,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 214,
        'endLine' => 217,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Tests\\Feature\\Returns',
        'declaringClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'implementingClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'currentClassName' => 'Tests\\Feature\\Returns\\PathCoverageTest',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));