class kdQuiz {
  constructor(questionsData, styleName, containerElement) {
    this.questionsData = questionsData;
    this.styleName = styleName;
    this.currentQuestionIndex = 0;
    this.correctAnswersCount = 0;
    this.containerElement = containerElement;
    this.quizElement = null;
    this.cardFront = null;
    this.cardBack = null;
    this.createQuizElement();
    this.loadQuestion();
  }

  createMeta(parent, itemprop, content) {
    const meta = document.createElement("meta");
    meta.setAttribute("itemprop", itemprop);
    meta.content = content;
    parent.appendChild(meta);
  }

  createQuizElement() {
    // Create the main quiz card container
    this.quizElement = document.createElement("div");
    this.quizElement.className = "kdquiz-card " + this.styleName;
    this.quizElement.setAttribute("itemscope", "");
    this.quizElement.setAttribute(
      "itemtype",
      "http://schema.org/SoftwareApplication"
    );

    this.createMeta(this.quizElement, "name", "Interactive Quiz");
    this.createMeta(
      this.quizElement,
      "description",
      "Allows you to create a simple interactive quiz on any page or post, requires Wordpress"
    );
    this.createMeta(this.quizElement, "softwareVersion", "1.3.4");
    this.createMeta(this.quizElement, "operatingSystem", "Web/Wordpress");

    const authorDiv = document.createElement("div");
    authorDiv.setAttribute("itemprop", "author");
    authorDiv.setAttribute("itemscope", "");
    authorDiv.setAttribute("itemtype", "http://schema.org/Person");
    this.createMeta(authorDiv, "name", "Nikos Konstas");

    const authorUrl = document.createElement("link");
    authorUrl.setAttribute("itemprop", "url");
    authorUrl.href = "https://github.com/nkonstas/wordpress-quiz";
    authorDiv.appendChild(authorUrl);

    this.quizElement.appendChild(authorDiv);

    // Create the inner container for flipping effect
    this.cardInner = document.createElement("div");
    this.cardInner.className = "kdquiz-card-inner";

    // Create the front and back faces (initially empty)
    this.cardFront = this.createCardFace("front");
    this.cardBack = this.createCardFace("back");

    // Append the front and back faces to the card inner container
    this.cardInner.appendChild(this.cardFront);
    this.cardInner.appendChild(this.cardBack);

    // Append the inner container to the main container
    this.quizElement.appendChild(this.cardInner);

    // Append the quiz card to the container element
    this.containerElement.appendChild(this.quizElement);
  }

  createCardFace(faceType) {
    const face = document.createElement("div");
    face.className = `kdquiz-card-face kdquiz-card-${faceType}`;
    return face;
  }

  loadQuestion() {
    // Update the current question data
    this.questionData = this.questionsData[this.currentQuestionIndex];

    // Update the front face with the current question
    this.updateFrontFace();

    // Reset the flip state
    this.quizElement.classList.remove("kdquiz-flipped");

    this.recordViewedQuestion(this.questionData.questionId);
    this.incrementQuestionViewCount(this.questionData.questionId);
  }

  recordViewedQuestion(questionId) {
    if (!questionId) return;

    const viewedQuestions =
      JSON.parse(localStorage.getItem("viewedQuestions")) || [];
    if (!viewedQuestions.includes(questionId)) {
      viewedQuestions.push(questionId);

      // Limit to the last X questions
      const maxHistory = 10; // The number of questions to remember
      if (viewedQuestions.length > maxHistory) {
        viewedQuestions.splice(0, viewedQuestions.length - maxHistory);
      }

      localStorage.setItem("viewedQuestions", JSON.stringify(viewedQuestions));
    }
  }

  incrementQuestionViewCount(questionId) {
    fetch(kdQuizAjax.ajax_url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        action: "kdquiz_increment_view_count",
        nonce: kdQuizAjax.nonce,
        question_id: questionId
      })
    })
    .then(response => response.json())
    .then(data => {
      if (!data.success) {
        console.error("Failed to increment view count for question ID:", questionId);
      }
    })
    .catch(error => console.error('Error:', error));
  }

  recordQuizAnswer(questionId, isCorrect) {
    fetch(kdQuizAjax.ajax_url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        action: "kdquiz_record_answer",
        nonce: kdQuizAjax.nonce,
        question_id: questionId,
        is_correct: isCorrect,
      })
    })
    .then(response => response.json())
    .then(data => {
      if (!data.success) {
        console.error("Failed to record answer for question ID:", questionId);
      }
    })
    .catch(error => console.error('Error:', error));
  }  

  updateFrontFace() {
    // Clear previous content in the front face
    this.cardFront.innerHTML = "";

    const questionText = document.createElement("p");
    questionText.className = "kdquiz-question";
    questionText.textContent = this.questionData.questionText;

    const optionsList = document.createElement("ul");
    optionsList.className = "kdquiz-options";

    this.questionData.options.forEach((option, index) => {
      const optionItem = document.createElement("li");
      optionItem.className = "kdquiz-option";
      optionItem.textContent = option.optionText;
      optionsList.appendChild(optionItem);

      // Add click event listener for each option
      optionItem.addEventListener("click", () => {
        this.isAnswerCorrect =
          this.questionData.correctOptionId ===
          this.questionData.options[index].optionId;
        if (this.isAnswerCorrect) {
          this.correctAnswersCount++;
        }
        this.updateBackFace();
        this.quizElement.classList.add("kdquiz-flipped");
        this.recordQuizAnswer(
          this.questionData.questionId,
          this.isAnswerCorrect
        );
      });
    });

    this.cardFront.appendChild(questionText);
    this.cardFront.appendChild(optionsList);
  }

  updateBackFace() {
    this.showAnswerExplanation();
    this.showNextButton();
  }

  showAnswerExplanation() {
    const cardBack = this.quizElement.querySelector(".kdquiz-card-back");
    cardBack.innerHTML = ""; // Clear previous content

    const feedbackText = document.createElement("p");
    feedbackText.className = this.isAnswerCorrect
      ? "kdquiz-correct"
      : "kdquiz-incorrect";
    feedbackText.innerHTML = this.isAnswerCorrect
      ? '<span class="kdquiz-answer-icon"></span>' + kdQuizAjax.text_correct_answer
      : '<span class="kdquiz-answer-icon"></span>' + kdQuizAjax.text_wrong_answer;

    const explanationText = document.createElement("p");
    explanationText.className = "kdquiz-answer";
    explanationText.textContent = this.questionData.explanation;

    cardBack.appendChild(feedbackText);
    cardBack.appendChild(explanationText);
  }

  showNextButton() {
    const nextButton = document.createElement("button");
    nextButton.className = "kdquiz-action";

    if (this.currentQuestionIndex < this.questionsData.length - 1) {
      nextButton.textContent = kdQuizAjax.text_next_question_raw;
      nextButton.addEventListener("click", () => {
        this.currentQuestionIndex++;
        this.loadQuestion();
        this.quizElement.classList.remove("kdquiz-flipped");
      });
    } else {
      nextButton.textContent = kdQuizAjax.text_next_view_score_raw;
      nextButton.addEventListener("click", () => {
        this.displayFinalScore(
          this.correctAnswersCount,
          this.questionsData.length
        );
        this.quizElement.classList.remove("kdquiz-flipped");
      });
    }

    const cardBack = this.quizElement.querySelector(".kdquiz-card-back");
    cardBack.appendChild(nextButton);
  }

  getGrade(scorePercentage) {
    if (scorePercentage >= 90) return "A";
    if (scorePercentage >= 70) return "B";
    if (scorePercentage >= 50) return "C";
    return "F";
  }

  displayFinalScore(correctAnswersCount, totalQuestions) {
    const scorePercentage = (correctAnswersCount / totalQuestions) * 100;
    const grade = this.getGrade(scorePercentage);

    let gradeClass;
    let gradeText;
    let feedbackMessage;
    switch (grade) {
      case "A":
        gradeClass = "kdquiz-final-a";
        gradeText = kdQuizAjax.kdquiz_text_score_grade_a;
        feedbackMessage = kdQuizAjax.kdquiz_text_score_grade_a_message;
        break;
      case "B":
        gradeClass = "kdquiz-final-b";
        gradeText = kdQuizAjax.kdquiz_text_score_grade_b;
        feedbackMessage = kdQuizAjax.kdquiz_text_score_grade_b_message;
        break;
      default:
      case "C":
        gradeClass = "kdquiz-final-c";
        gradeText = kdQuizAjax.kdquiz_text_score_grade_c;
        feedbackMessage = kdQuizAjax.kdquiz_text_score_grade_c_message;
        break;
      case "F":
        gradeClass = "kdquiz-final-f";
        gradeText = kdQuizAjax.kdquiz_text_score_grade_f;
        feedbackMessage = kdQuizAjax.kdquiz_text_score_grade_f_message;
        break;
    }

    this.cardFront.innerHTML = `
        <div class="${gradeClass}">
        <p class="kdquiz-final-grade">${kdQuizAjax.kdquiz_text_score_grade} <span>${gradeText}</span></p>
        <p class="kdquiz-final-score">${kdQuizAjax.kdquiz_text_score_percentage} ${scorePercentage.toFixed(0)}%</p>
        <p class="kdquiz-final-message">${feedbackMessage}</p>
        </div>`;
  }

  // Additional methods like handleVisibility can be added here
}

class kdQuizMgr {
  constructor() {
    this.init();
  }

  init() {
    if (typeof kdQuizAjax !== "undefined" && kdQuizAjax.debug_auto_insert) {
      console.log("[kdquiz] configuration", kdQuizAjax);
    }

    document.addEventListener("DOMContentLoaded", () => {
      const selectors = [kdQuizAjax.element_selector].filter(Boolean);
      if (kdQuizAjax.legacy_element_selector) {
        selectors.push(kdQuizAjax.legacy_element_selector);
      }

      if (kdQuizAjax.debug_auto_insert) {
        console.log("[kdquiz] auto insert bootstrap", {
          elementSelector: kdQuizAjax.element_selector,
          legacySelector: kdQuizAjax.legacy_element_selector,
          autoInsertEnabled: !!kdQuizAjax.auto_insert_enabled,
          containerSelector: kdQuizAjax.container_selector,
          headingSelector: kdQuizAjax.heading_selector,
          headingMatch: kdQuizAjax.heading_match,
          minDistancePx: kdQuizAjax.min_distance,
        });
      }

      const quizElements = document.querySelectorAll(selectors.join(", "));

      if (quizElements.length === 0 && kdQuizAjax.auto_insert_enabled) {
        const containerSelector = (kdQuizAjax.container_selector || "").trim();
        const minDistancePx = Math.max(
          0,
          parseInt(kdQuizAjax.min_distance, 10) || 0
        );

        const headingMatchPattern = kdQuizAjax.heading_match.trim();
        let headingMatchRegex = null;
        if (headingMatchPattern) {
          const escapedPattern = headingMatchPattern
            .replace(/[-\/\\^$*+?.()|[\]{}]/g, "\\$&")
            .replace(/\*/g, ".*");
          headingMatchRegex = new RegExp(escapedPattern, "i");
        }

        const containers = containerSelector
          ? Array.from(document.querySelectorAll(containerSelector))
          : [document.body];

        if (!containers.length) {
          if (kdQuizAjax.debug_auto_insert) {
            console.warn(
              "[kdquiz] no containers matched selector, aborting auto insert",
              containerSelector
            );
          }
          return;
        }

        const container = containers[0];
        if (kdQuizAjax.debug_auto_insert) {
          console.log("[kdquiz] evaluating container", containerSelector || "(document.body)", {
            container,
            headingSelector: kdQuizAjax.heading_selector,
          });
        }

        const containerTop =
          container.getBoundingClientRect().top + window.scrollY;
        const candidates = Array.from(
          container.querySelectorAll(kdQuizAjax.heading_selector)
        );

        let insertionHeading = null;
        candidates.forEach((heading, index) => {
          const headingTop = heading.getBoundingClientRect().top + window.scrollY;
          const relativeTop = headingTop - containerTop;
          const matchesPattern =
            !headingMatchRegex || headingMatchRegex.test(heading.textContent);

          if (kdQuizAjax.debug_auto_insert) {
            console.log("[kdquiz] evaluating heading", {
              index,
              text: heading.textContent.trim(),
              containerTop: Math.round(containerTop),
              headingTop: Math.round(headingTop),
              relativeTop: Math.round(relativeTop),
              minDistancePx,
              matchesPattern,
            });
          }

          if (!insertionHeading && relativeTop >= minDistancePx && matchesPattern) {
            insertionHeading = heading;
          }
        });

        if (!insertionHeading) {
          if (kdQuizAjax.debug_auto_insert) {
            console.warn("[kdquiz] auto insert aborted, no headings satisfied offset/pattern", {
              totalCandidates: candidates.length,
              minDistancePx,
              headingMatch: headingMatchPattern || "(none)",
            });
          }
          return;
        }

        if (kdQuizAjax.debug_auto_insert) {
          console.log("[kdquiz] auto insert target found", {
            headingText: insertionHeading.textContent.trim(),
            minDistancePx,
          });
        }

        const newDiv = document.createElement("div");
        newDiv.id = kdQuizAjax.element_selector.replace("#", "");
        newDiv.className = "kdquiz-container kd-quiz-container";
        insertionHeading.parentNode.insertBefore(newDiv, insertionHeading);
        this.fetchQuestionsAndCreateQuiz(newDiv);
        return;
      } else if (quizElements.length > 0) {
        // If there are already quiz elements, fetch questions for the first one
        this.fetchQuestionsAndCreateQuiz(quizElements[0]);
      }
    });
  }

  fetchQuestionsAndCreateQuiz(el) {
    const viewedQuestions = localStorage.getItem("viewedQuestions") || "[]";
  
    fetch(kdQuizAjax.ajax_url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        action: "kdquiz_fetch_random_questions",
        number: kdQuizAjax.questions,
        nonce: kdQuizAjax.nonce,
        viewed_questions: viewedQuestions,
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const quiz = new kdQuiz(data.data, kdQuizAjax.style, el);
      } else {
        console.error("Failed to fetch quiz questions.");
      }
    })
    .catch(error => console.error('Error:', error));
  }  
}

const manager = new kdQuizMgr();
