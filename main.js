/**
 * Quiz Module JavaScript
 */
/* test */
jQuery(function($) {
    $(document).ready(function() {
        // Initialize each quiz module
        $('.mod-quiz').each(function() {
            var module = $(this);
            var moduleId = module.attr('id').replace('mod-quiz-', '');
            var options = window['MOD_POLLS_OPTIONS_' + moduleId];

            initQuiz(module, options);
        });
    });

    /**
     * Initialize the quiz
     * @param {jQuery} module jQuery object of the module container
     * @param {Object} options Quiz options
     */
    function initQuiz(module, options) {
        var quizContent = module.find('.quiz-content');
        var quizLoading = module.find('.quiz-loading');
        var quizQuestions = module.find('.quiz-questions');
        var quizActions = module.find('.quiz-actions');
        var submitButton = module.find('.submit-quiz');
        var quizResults = module.find('.quiz-results');
        var currentQuestionIndex = 0;
        var totalQuestions = 0;
        var accordionId = '';
        // Новая переменная для хранения информации о правильных ответах
        var correctAnswersData = {};

        // Load questions immediately if the quiz content is visible
        if (quizContent.is(':visible')) {
            loadQuestions(options);
        }

        // Submit quiz button click
        submitButton.on('click', function() {
            submitQuiz();
        });

        /**
         * Load questions from the server
         */
        function loadQuestions() {
            $.ajax({
                url: Joomla.getOptions('system.paths').root + '/index.php?option=com_ajax&module=polls&method=getQuestions&format=json',
                type: 'POST',
                data: {
                    numQuestions: options.numQuestions,
                    section: options.section,
                    moduleId: options.moduleId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        if (response.data.error) {
                            if (response.data.error === 'already_taken') {
                                showError(Joomla.JText._('MOD_POLLS_ALREADY_TAKEN_TODAY'));
                                // Reload page after 2 seconds to show the "already taken" message
                                setTimeout(function() {
                                    window.location.reload();
                                }, 2000);
                            } else {
                                showError(response.data.error);
                            }
                        } else {
                            renderQuestions(response.data);
                        }
                    } else {
                        showError(Joomla.JText._('MOD_POLLS_ERROR_LOADING'));
                    }
                },
                error: function() {
                    showError(Joomla.JText._('MOD_POLLS_ERROR_LOADING'));
                }
            });
        }

        /**
         * Render questions to the DOM
         * @param {Array} questions Array of question objects
         */
        function renderQuestions(questions) {
            quizLoading.hide();
            quizQuestions.empty();

            if (questions.length === 0) {
                showError(Joomla.JText._('MOD_POLLS_ERROR_LOADING'));
                return;
            }

            // Сохраняем общее количество вопросов
            totalQuestions = questions.length;

            // Create accordion container
            accordionId = 'quiz-accordion-' + options.moduleId;
            var accordion = $('<div class="accordion" id="' + accordionId + '"></div>');

            // Очищаем хранилище правильных ответов перед новым рендерингом
            correctAnswersData = {};

            // Add each question
            $.each(questions, function(index, question) {
                var questionId = 'question-' + options.moduleId + '-' + question.id;
                var collapseId = 'collapse-' + options.moduleId + '-' + index;
                var headingId = 'heading-' + options.moduleId + '-' + index;
                var card = $('<div class="card quiz-question" data-id="' + question.id + '" data-index="' + index + '"></div>');

                // Инициализируем хранилище правильных ответов для текущего вопроса
                correctAnswersData[question.id] = {};

                // Card header with question
                var cardHeader = $('<div class="card-header" id="' + headingId + '"></div>');
                var heading = $('<h5 class="mb-0"></h5>');

                // Начальное состояние - только первый вопрос expanded
                var isExpanded = (index === 0);

                var button = $('<button class="btn-link ' + (isExpanded ? '' : 'collapsed') + '" type="button" data-toggle="collapse" data-target="#' + collapseId + '" aria-expanded="' + (isExpanded ? 'true' : 'false') + '" aria-controls="' + collapseId + '">' + question.question + '</button>');

                // Деактивируем все кнопки кроме первой
                if (index > 0) {
                    button.addClass('disabled');
                    button.attr('disabled', 'disabled');
                }

                heading.append(button);
                cardHeader.append(heading);

                // Card body with answers
                // Важно: НЕ добавляем класс show при генерации, а сделаем это позже через jQuery
                var collapse = $('<div id="' + collapseId + '" class="collapse" aria-labelledby="' + headingId + '" data-parent="#' + accordionId + '"></div>');
                var cardBody = $('<div class="card-body"></div>');

                // Answers container
                var answersContainer = $('<div class="quiz-answers-container"></div>');

                // Add answers
                $.each(question.answers, function(answerIndex, answer) {
                    var answerId = 'answer-' + options.moduleId + '-' + answer.id;
                    var answerDiv = $('<div class="form-check quiz-answer mb-2"></div>');
                    
                    // Удаляем атрибут data-rightly и сохраняем информацию в отдельной структуре данных
                    var answerInput = $('<input class="form-check-input" type="checkbox" id="' + answerId + '" name="' + questionId + '" value="' + answer.id + '">');
                    
                    // Сохраняем информацию о правильности ответа в JavaScript переменной
                    correctAnswersData[question.id][answer.id] = parseInt(answer.rightly) === 1;
                    
                    var answerLabel = $('<label class="form-check-label" for="' + answerId + '">' + answer.answer + '</label>');

                    answerDiv.append(answerInput, answerLabel);
                    answersContainer.append(answerDiv);
                });

                // Add button container at the bottom
                var buttonContainer = $('<div class="quiz-button-container mt-3 d-flex"></div>');

                // Add continue button for each question except the last one
                if (index < questions.length - 1) {
                    var continueButton = $('<button class="btn btn-primary continue-button ml-auto" data-index="' + index + '">' + Joomla.JText._('MOD_POLLS_NEXT_QUESTION') + '</button>');
                    buttonContainer.append(continueButton);

                    // Add click handler for continue button
                    continueButton.on('click', function(e) {
                        e.preventDefault(); // Предотвращаем возможные побочные эффекты
                        var questionIndex = parseInt($(this).data('index'));

                        // Включаем следующий вопрос
                        enableNextQuestion(questionIndex + 1);

                        // Переходим к следующему вопросу, закрыв все остальные
                        moveToQuestion(questionIndex + 1);
                    });
                } else {
                    // For the last question, add a submit button if not already present in the main container
                    var submitBtn = $('<button class="btn btn-success continue-button ml-auto">' + Joomla.JText._('MOD_POLLS_SUBMIT_ANSWERS') + '</button>');
                    submitBtn.on('click', function() {
                        submitQuiz();
                    });
                    buttonContainer.append(submitBtn);
                }

                cardBody.append(answersContainer, buttonContainer);
                collapse.append(cardBody);

                card.append(cardHeader, collapse);
                accordion.append(card);
            });

            quizQuestions.append(accordion);
            quizQuestions.show();

            // Скрываем общую кнопку отправки, т.к. теперь она есть в последнем вопросе
            quizActions.hide();

            // Программно открываем первый вопрос ПОСЛЕ того, как DOM полностью построен
            setTimeout(function() {
                // Сначала закрываем все вкладки для уверенности
                $('#' + accordionId + ' .collapse').collapse('hide');
                // Затем открываем только первую вкладку
                $('#collapse-' + options.moduleId + '-0').collapse('show');
            }, 100);

            // Добавляем обработчик для отслеживания переключения вкладок
            accordion.on('show.bs.collapse', function(e) {
                var target = $(e.target);
                var index = parseInt(target.closest('.quiz-question').data('index'));

                // Не позволяем открыть следующие вопросы, если до них еще не дошли
                if (index > currentQuestionIndex) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }

                // При любом открытии вкладки убеждаемся, что все остальные закрыты
                $('#' + accordionId + ' .collapse').not(e.target).collapse('hide');
            });

            // Управление фокусом после открытия вкладки
            accordion.on('shown.bs.collapse', function(e) {
                var target = $(e.target);
                var card = target.closest('.card');
                var index = parseInt(card.data('index'));

                // Обновляем текущий индекс
                currentQuestionIndex = Math.max(currentQuestionIndex, index);

                // Прокрутка к карточке
                /*$('html, body').animate({
                    scrollTop: card.offset().top - 20
                }, 300);

                // Фокус на первом чекбоксе
                target.find('input[type="checkbox"]').first().focus();*/
            });
        }

        /**
         * Move to a specific question
         * @param {number} index Question index to open
         */
        function moveToQuestion(index) {
            // Сначала закрываем все вкладки
            $('#' + accordionId + ' .collapse').collapse('hide');

            // Затем открываем нужную вкладку после небольшой задержки
            setTimeout(function() {
                $('#collapse-' + options.moduleId + '-' + index).collapse('show');
            }, 400); // Даем время для анимации закрытия
        }

        /**
         * Enable next question button and make it clickable
         * @param {number} index Question index to enable
         */
        function enableNextQuestion(index) {
            var button = $('#heading-' + options.moduleId + '-' + index + ' button');
            button.removeClass('disabled').removeAttr('disabled');

            // Обновляем текущий доступный индекс
            currentQuestionIndex = Math.max(currentQuestionIndex, index);
        }

        /**
         * Submit quiz answers
         */
        function submitQuiz() {
            var userAnswers = [];
            var correctAnswers = 0;

            // Collect user answers and calculate score
            module.find('.quiz-question').each(function() {
                var question = $(this);
                var questionId = question.data('id');
                var questionCorrect = true;
                var userAnswerIds = [];

                // Check each answer option
                question.find('input[type="checkbox"]').each(function() {
                    var answer = $(this);
                    var isChecked = answer.prop('checked');
                    var answerId = answer.val();
                    
                    // Используем сохраненные данные о правильности ответа вместо атрибута
                    var isRightly = correctAnswersData[questionId][answerId];

                    // If checked, add to user answers
                    if (isChecked) {
                        userAnswerIds.push(answerId);
                    }

                    // Check if answer is correct
                    if ((isChecked && !isRightly) || (!isChecked && isRightly)) {
                        questionCorrect = false;
                    }
                });

                // Add to user answers array
                userAnswers.push({
                    questionId: questionId,
                    answerIds: userAnswerIds
                });

                // Increment correct answers count
                if (questionCorrect) {
                    correctAnswers++;
                }
            });

            // Calculate score percentage
            var scorePercentage = Math.round((correctAnswers / totalQuestions) * 100);
            var isPassed = scorePercentage >= options.passingScore;

            // Show results
            displayResults(scorePercentage, isPassed);

            // Save results to server
            saveResults(isPassed ? 'yes' : 'no', scorePercentage, userAnswers);
        }

        /**
         * Display quiz results
         * @param {number} score Score percentage
         * @param {boolean} passed Whether the user passed the quiz
         */
        function displayResults(score, passed) {
            quizContent.hide();

            var quizScore = module.find('.quiz-score');
            var quizStatus = module.find('.quiz-status');
            var resultsContainer = module.find('.quiz-results-details');

            // Очищаем контейнер результатов, если он уже существует
            if (resultsContainer.length === 0) {
                resultsContainer = $('<div class="quiz-results-details mt-4"></div>');
                quizResults.append(resultsContainer);
            } else {
                resultsContainer.empty();
            }

            // Set score and status
            quizScore.text(Joomla.JText._('MOD_POLLS_YOUR_SCORE').replace('%s', score));

            if (passed) {
                quizStatus.text(Joomla.JText._('MOD_POLLS_PASSED')).addClass('passed alert alert-success');
            } else {
                quizStatus.text(Joomla.JText._('MOD_POLLS_FAILED')).addClass('failed alert alert-danger');
            }

            // Добавляем заголовок для списка вопросов
            resultsContainer.append('<h4 class="mt-4 mb-3">Результаты по вопросам:</h4>');

            // Создаем список вопросов
            var questionsList = $('<ul class="list-group questions-list"></ul>');
            
            module.find('.quiz-question').each(function(index) {
                var question = $(this);
                var questionId = question.data('id');
                var questionText = question.find('.card-header button').text();
                var questionCorrect = true;
                
                // Проверяем каждый ответ на правильность
                question.find('input[type="checkbox"]').each(function() {
                    var answer = $(this);
                    var answerId = answer.val();
                    var isChecked = answer.prop('checked');
                    var isRightly = correctAnswersData[questionId][answerId];
                    
                    // Если хотя бы один ответ неправильный, весь вопрос считается неправильным
                    if ((isChecked && !isRightly) || (!isChecked && isRightly)) {
                        questionCorrect = false;
                    }
                });
                
                // Создаем элемент списка для вопроса
                var listItem = $('<li class="list-group-item"></li>');
                
                // Устанавливаем цвет в зависимости от правильности ответа
                if (questionCorrect) {
                    listItem.addClass('list-group-item-success');
                    listItem.html('<i class="fa fa-check-circle"></i> <strong>Верно:</strong> ' + questionText);
                } else {
                    listItem.addClass('list-group-item-danger');
                    listItem.html('<i class="fa fa-times-circle"></i> <strong>Неверно:</strong> ' + questionText);
                }
                
                questionsList.append(listItem);
            });
            
            resultsContainer.append(questionsList);
            quizResults.show();

            // Прокрутка к результатам
            $('html, body').animate({
                scrollTop: quizResults.offset().top - 65
            }, 300);
            
            // Добавляем стили для иконок, если нет Font Awesome
            if ($('#quiz-results-styles').length === 0) {
                $('head').append(`
                    <style id="quiz-results-styles">
                        .fa-check-circle:before {
                            content: "✓";
                            margin-right: 5px;
                        }
                        .fa-times-circle:before {
                            content: "✗";
                            margin-right: 5px;
                        }
                    </style>
                `);
            }
        }

        /**
         * Save quiz results to the server
         * @param {string} result Result ('yes' or 'no')
         * @param {number} score User score percentage
         * @param {Array} userAnswers Array of user answers
         */
        function saveResults(result, score, userAnswers) {
            $.ajax({
                url: Joomla.getOptions('system.paths').root + '/index.php?option=com_ajax&module=polls&method=saveResult&format=json',
                type: 'POST',
                data: {
                    result: result,
                    score: score,
                    userAnswers: userAnswers,
                    moduleId: options.moduleId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data && response.data.error) {
                        console.error('Ошибка сохранения результата:', response.data.error);
                    }
                }
            });
        }

        /**
         * Show error message
         * @param {string} message Error message
         */
        function showError(message) {
            quizLoading.hide();
            quizQuestions.html('<div class="alert alert-danger">' + message + '</div>').show();
        }
    }
});