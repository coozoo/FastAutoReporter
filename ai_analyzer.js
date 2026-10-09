// AI log analysis for suite.php: shared context building, Copilot analysis, Custom AI context
// needs: AI_CONFIG and repoPrompts defined by suite.php, #ai_tool_selector and #ai_repo_selector on page
// plain js, no libraries

// =======================================================================
// AI LOG ANALYZER (Auto-switches between Free & Enterprise based on token start chars)
// =======================================================================

function fetchJiraContextMarkdown(ticketId) {
    return fetch('getxraycasepage.php?caseid=' + encodeURIComponent(ticketId) + '&output=json')
        .then(res => res.ok ? res.json() : null)
        .then(data => {
            if (!data || data.error) return null;
            let md = "### JIRA TICKET: " + data.jira.key + " (" + data.jira.type + ")\n";
            md += "**Summary:** " + data.jira.summary + "\n";
            
            // --- START: Parse Atlassian Document Format (ADF) Description ---
            if (data.jira.description) {
                md += "**Description:**\n";
                if (typeof data.jira.description === 'string') {
                    md += data.jira.description + "\n\n";
                } else if (typeof data.jira.description === 'object') {
                    // Recursive parser for ADF JSON
                    function parseAdf(node) {
                        if (!node) return "";
                        if (typeof node === 'string') return node;
                        let res = "";
                        if (node.type === 'text') {
                            res += node.text;
                        } else if (node.type === 'paragraph' && node.content) {
                            res += node.content.map(parseAdf).join("") + "\n\n";
                        } else if (node.type === 'heading' && node.content) {
                            let lvl = (node.attrs && node.attrs.level) ? node.attrs.level : 1;
                            res += "#".repeat(lvl) + " " + node.content.map(parseAdf).join("") + "\n\n";
                        } else if (node.type === 'bulletList' && node.content) {
                            res += node.content.map(li => "- " + parseAdf(li)).join("") + "\n";
                        } else if (node.type === 'orderedList' && node.content) {
                            res += node.content.map((li, i) => (i+1) + ". " + parseAdf(li)).join("") + "\n";
                        } else if (node.type === 'listItem' && node.content) {
                            res += node.content.map(parseAdf).join("").trim() + "\n";
                        } else if (node.content) {
                            res += node.content.map(parseAdf).join("");
                        }
                        return res;
                    }
                    md += parseAdf(data.jira.description) + "\n";
                }
            }
            // --- END: Parse Atlassian Document Format ---

            if (data.xray && data.xray.steps && data.xray.steps.length > 0) {
                md += "**Xray Steps:**\n";
                data.xray.steps.forEach(s => {
                    md += "Step " + s.step + ": " + s.action + "\n";
                    if (s.data) md += " - Data: " + s.data + "\n";
                    if (s.result) md += " - Expected: " + s.result + "\n";
                });
            }
            return { markdown: md, links: data.jira.links || [] };
        }).catch(e => null);
}

// =======================================================================
// SHARED CONTEXT BUILDING (used by every AI tool)
// context is built only from logs already loaded on the page (expanded test row)
// =======================================================================

function aiGetTestInfo(testid) {
    var info = {
        testid: testid,
        targetColElement: document.getElementById("testscolumn_" + testid),
        testMethodName: 'Unknown Test',
        xrayId: 'Unknown Xray',
        testStatus: 'Unknown'
    };
    var testRow = document.getElementById('testrow_' + testid);
    if (testRow) {
        if (testRow.cells[0]) info.testMethodName = testRow.cells[0].getAttribute('value') || info.testMethodName;
        if (testRow.cells[1]) info.xrayId = testRow.cells[1].getAttribute('value') || info.xrayId;
        if (testRow.cells[3]) info.testStatus = testRow.cells[3].getAttribute('value') || info.testStatus;
    }
    return info;
}

function aiIsTestLoaded(info) {
    if (!info.targetColElement || !info.targetColElement.innerHTML.length) {
        alert("Please expand the test row first to load the logs before using AI analysis.");
        return false;
    }
    return true;
}

// logs are inside testdetails.php iframe
function aiGetLogNode(targetColElement) {
    var logNode = targetColElement;
    var frames = targetColElement.querySelectorAll('iframe');
    if (frames.length > 0) {
        try { logNode = frames[0].contentDocument.body; } catch (e) { console.log("Failed to read iframe:", e); }
    }
    return logNode;
}

function aiGetContainer(info) {
    var aiContainerId = 'ai_container_' + info.testid;
    var aiContainer = document.getElementById(aiContainerId);
    if (!aiContainer) {
        aiContainer = document.createElement('div');
        aiContainer.id = aiContainerId;
        info.targetColElement.insertBefore(aiContainer, info.targetColElement.firstChild);
    }
    return aiContainer;
}

function aiGetSelectedRepo() {
    var repoSelector = document.getElementById('ai_repo_selector');
    return repoSelector.options[repoSelector.selectedIndex].value;
}

// search test method and class from stack trace in selected repo (fetch_github_code.php)
function aiFetchSourceCode(logText, testMethodName, repoName) {
    var fileNameToSearch = '';
    var classMatch = logText.match(/at com\.qa\.[^(]+\(([^:]+\.java):\d+\)/);
    if (classMatch && classMatch[1]) {
        fileNameToSearch = classMatch[1];
    } else {
        var fallbackMatch = logText.match(/([A-Z][a-zA-Z0-9_]+\.java):\d+/);
        if (fallbackMatch && fallbackMatch[1]) {
            fileNameToSearch = fallbackMatch[1];
        }
    }

    // Build array of unique search terms
    var searchTerms = [testMethodName];

    // Strip .java so GitHub searches for the Class name inside the file!
    var cleanClassName = fileNameToSearch ? fileNameToSearch.replace('.java', '') : '';

    if (cleanClassName && cleanClassName !== testMethodName) {
        searchTerms.push(cleanClassName);
    }

    console.log('🎯 Searching GitHub for terms: ', searchTerms);

    var fetchPromises = searchTerms.map(function(term) {
        var cleanQuery = term.trim();
        var cleanRepo = repoName.trim();
        console.log('🚀 Sending to proxy -> query: ' + cleanQuery + ' | repo: ' + cleanRepo);

        return fetch('fetch_github_code.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ query: cleanQuery, repo: cleanRepo, limit: 3 })
        }).then(res => {
            console.log('📡 Proxy HTTP Status for ' + cleanQuery + ': ' + res.status + ' ' + res.statusText);
            return res.text();
        }).then(text => {
            console.log('📦 Proxy Raw Response Length for ' + cleanQuery + ': ' + text.length);
            if (text.length < 200) console.log('📦 Proxy Raw Body: ' + text);
            return text;
        }).catch(e => {
            console.error('🔥 Fetch Exception for ' + cleanQuery + ':', e);
            return 'Fetch Exception: ' + e.message;
        });
    });

    // Resolve all promises concurrently
    return Promise.all(fetchPromises).then(function(results) {
        var combined = '';
        var seenCodes = {};

        for (var i = 0; i < results.length; i++) {
            var code = results[i];
            console.log('--- 🔎 Checking proxy result for: ' + searchTerms[i] + ' ---');

            if (code && typeof code === 'string' && code.indexOf('Code not found in repository') === -1 && code.trim() !== '' && code.indexOf('Fetch Exception') === -1) {
                if (!seenCodes[code]) {
                    seenCodes[code] = true;
                    combined += '\n--- ACTUAL REPOSITORY SOURCE CODE (' + searchTerms[i] + ') ---\n' + code + '\n';
                    console.log('✅ KEPT code for: ' + searchTerms[i]);
                } else {
                    console.log('⚠️ IGNORED duplicate code for: ' + searchTerms[i]);
                }
            } else {
                console.log('❌ THREW AWAY result for: ' + searchTerms[i] + ' (Was empty, 404, or not found)');
            }
        }
        return combined;
    });
}

// jira/xray story, linked issues are loaded only when withLinks is true
function aiFetchJiraContext(xrayId, withLinks, onLinks) {
    if (!xrayId || xrayId === 'Unknown Xray') {
        return Promise.resolve("No Jira context available.");
    }
    return fetchJiraContextMarkdown(xrayId).then(mainTicket => {
        if (!mainTicket) return "Failed to load Jira context for " + xrayId;
        let contextText = mainTicket.markdown + "\n";
        if (withLinks && mainTicket.links.length > 0) {
            if (onLinks) onLinks(mainTicket.links.length);
            let linkPromises = mainTicket.links.map(link => fetchJiraContextMarkdown(link));
            return Promise.all(linkPromises).then(linkedTickets => {
                contextText += "\n--- LINKED ISSUES CONTEXT ---\n";
                linkedTickets.forEach(lt => { if (lt) contextText += lt.markdown + "\n"; });
                return contextText;
            });
        }
        return contextText;
    });
}

// log lines from testdetails table, styled (font) lines are important ones
function aiCollectLogLines(logDomNode, maxLineChars) {
    var allRows = logDomNode.querySelectorAll('tr');
    var allLines = [];
    var importantLines = [];

    for (var i = 0; i < allRows.length; i++) {
        var tr = allRows[i];
        var codeNode = tr.querySelector('td code');

        if (codeNode) {
            var rawLine = codeNode.textContent.trim();
            if (rawLine) {
                var cutLine = rawLine.length > maxLineChars ? rawLine.substring(0, maxLineChars) + '...[cut]' : rawLine;
                allLines.push(cutLine);
                if (codeNode.querySelector('font')) importantLines.push(cutLine);
            }
        }
    }
    return { allLines: allLines, importantLines: importantLines };
}

// keep start, important lines and end of logs for failed tests, tail for others
function aiTruncateLogs(allLines, importantLines, availChars, testStatus) {
    var fullText = allLines.join('\n');
    if (fullText.length <= availChars) {
        return fullText;
    }
    var statusUpper = testStatus.toUpperCase();
    if (statusUpper === 'ERROR' || statusUpper === 'FAIL' || statusUpper === 'SKIP') {
        var importantText = importantLines.join('\n');
        if (importantText.length > availChars) importantText = importantText.slice(-availChars);

        var remainingBudget = Math.max(0, availChars - importantText.length);
        var startBudget = Math.floor(remainingBudget / 3);
        var endBudget = remainingBudget - startBudget;

        var topLines = [];
        var topChars = 0;
        var topIndex = 0;

        while (topIndex < allLines.length && topChars + allLines[topIndex].length < startBudget) {
            topLines.push(allLines[topIndex]);
            topChars += allLines[topIndex].length + 1;
            topIndex++;
        }

        var bottomLines = [];
        var bottomChars = 0;
        var bottomIndex = allLines.length - 1;

        while (bottomIndex >= topIndex && bottomChars + allLines[bottomIndex].length < endBudget) {
            bottomLines.unshift(allLines[bottomIndex]);
            bottomChars += allLines[bottomIndex].length + 1;
            bottomIndex--;
        }

        return "--- START OF LOGS ---\n" + topLines.join('\n') + "\n\n" +
               "--- IMPORTANT STYLED LOGS (ERRORS/WARNINGS) ---\n```text\n" + importantText + "\n```\n\n" +
               "--- END OF LOGS (CRASH & STACK TRACE) ---\n" + bottomLines.join('\n');
    }
    return '...[TRUNCATED]...\n' + fullText.slice(-availChars);
}

// full context for test: source code -> jira -> logs within limits
// opts: maxTotalChars, maxLineChars, withLinkedIssues, onCodeLoaded(), onLinks(count)
// resolves {context, systemPrompt, repoName, hasSourceCode, logDomNode}
function aiBuildContext(info, opts) {
    var logText = aiGetLogNode(info.targetColElement);
    logText = logText.innerText || logText.textContent || '';
    var repoName = aiGetSelectedRepo();

    return aiFetchSourceCode(logText, info.testMethodName, repoName).then(sourceCode => {
        var filePath = sourceCode ? "Code securely loaded from backend." : "Unknown (could not find in repo)";
        if (opts.onCodeLoaded) opts.onCodeLoaded();

        return aiFetchJiraContext(info.xrayId, opts.withLinkedIssues, opts.onLinks).then(finalJiraContext => {
            var logDomNode = aiGetLogNode(info.targetColElement);
            var lines = aiCollectLogLines(logDomNode, opts.maxLineChars);

            if (lines.allLines.length === 0) throw new Error('Logs are empty (No <td><code> elements found).');

            var injectedContext = "Test Method: " + info.testMethodName + "\n" +
                                  "Repository File Path: " + filePath + "\n" +
                                  "Current Status: " + info.testStatus + "\n\n" +
                                  "--- JIRA / XRAY CONTEXT ---\n" +
                                  finalJiraContext + "\n\n";

            if (sourceCode && sourceCode.trim() !== '') {
                injectedContext += '--- ACTUAL REPOSITORY SOURCE CODE ---\n' + sourceCode + '\n\n';
                console.log('✅ Java Source Code successfully appended to prompt! Length: ' + sourceCode.length);
            } else {
                console.error('❌ NO SOURCE CODE WAS APPENDED! fetch_github_code.php returned: ' + sourceCode);
            }

            var availChars = opts.maxTotalChars - injectedContext.length;
            if (availChars < 2000) availChars = 2000;

            injectedContext += "LOGS:\n" + aiTruncateLogs(lines.allLines, lines.importantLines, availChars, info.testStatus);

            var systemPrompt = (typeof repoPrompts !== 'undefined' && repoPrompts[repoName]) ? repoPrompts[repoName] : AI_CONFIG.copilot.systemPrompt;
            console.log(systemPrompt);

            return {
                context: injectedContext,
                systemPrompt: systemPrompt,
                repoName: repoName,
                hasSourceCode: !!(sourceCode && sourceCode.trim() !== ''),
                logDomNode: logDomNode
            };
        });
    });
}

// screenshot links (getblob.php) from FAIL/ERROR log rows
function aiGetFailBlobLinks(logDomNode) {
    var blobLinks = [];
    var logRows = logDomNode.querySelectorAll('tr');
    for (var r = 0; r < logRows.length; r++) {
        if (logRows[r].textContent.indexOf('[FAIL]') > -1 || logRows[r].textContent.indexOf('[ERROR]') > -1) {
            var link = logRows[r].querySelector('a[href*="getblob.php"]');
            if (link && blobLinks.indexOf(link.href) === -1) blobLinks.push(link.href);
        }
    }
    return blobLinks;
}

// =======================================================================
// AI TOOL SELECTOR
// =======================================================================

function aiGetSelectedTool() {
    var toolSelector = document.getElementById('ai_tool_selector');
    if (toolSelector && toolSelector.selectedIndex > -1) {
        return toolSelector.options[toolSelector.selectedIndex].value;
    }
    return 'copilot';
}

function aiAnalyze(testid) {
    if (aiGetSelectedTool() === 'custom') {
        aiCustomContext(testid);
    } else {
        analyzeLogsWithUserKey(testid);
    }
}

// =======================================================================
// COPILOT (Auto-switches between Free & Enterprise based on token start chars)
// =======================================================================

function analyzeLogsWithUserKey(testid) {

    var FREE_MAX_TOTAL_CHARS = AI_CONFIG.copilot.freeMaxTotalChars;
    var FREE_MAX_LINE_CHARS = AI_CONFIG.copilot.freeMaxLineChars;
    var FREE_MODEL = AI_CONFIG.copilot.freeModel;

    var PAID_MAX_TOTAL_CHARS = AI_CONFIG.copilot.paidMaxTotalChars;
    var PAID_MAX_LINE_CHARS = AI_CONFIG.copilot.paidMaxLineChars;
    var PAID_MODEL = AI_CONFIG.copilot.paidModel;

    var info = aiGetTestInfo(testid);
    if (!aiIsTestLoaded(info)) return;

    var apiKey = localStorage.getItem('user_llm_api_key');
    if (!apiKey) {
        apiKey = prompt('Paste GitHub PAT (Free limits) OR Copilot IDE Token starting with ghu_ (Enterprise Limits):');
        if (!apiKey) return;
        localStorage.setItem('user_llm_api_key', apiKey);
    }

    // Auto-detect mode based on the token string!
    var isEnterprise = (apiKey.startsWith('ghu_') || apiKey.startsWith('gho_'));

    // Apply dynamic limits based on mode
    var MAX_TOTAL_CHARS = isEnterprise ? PAID_MAX_TOTAL_CHARS : FREE_MAX_TOTAL_CHARS;
    var MAX_LINE_CHARS = isEnterprise ? PAID_MAX_LINE_CHARS : FREE_MAX_LINE_CHARS;
    var selectedModel = isEnterprise ? PAID_MODEL : FREE_MODEL;

    var aiContainer = aiGetContainer(info);

    // Update UI styling based on mode
    var modeText = isEnterprise ? "🚀 [ENTERPRISE]" : "🤖 [FREE TIER]";
    var modeColor = isEnterprise ? "#8a2be2" : "#5c95f7";
    var modeBg = isEnterprise ? "#fdf5ff" : "#f9f9fc";

    aiContainer.innerHTML = "<div style='padding: 10px; font-weight: bold; color: " + modeColor + ";'>" + modeText + " Initializing AI Analysis for " + info.testMethodName + "...</div>";

    aiBuildContext(info, {
        maxTotalChars: MAX_TOTAL_CHARS,
        maxLineChars: MAX_LINE_CHARS,
        withLinkedIssues: isEnterprise,
        onCodeLoaded: function() {
            aiContainer.innerHTML = "<div style='padding: 10px; font-weight: bold; color: " + modeColor + ";'>" + modeText + " Fetching Context & Parsing logs (Limit: " + MAX_TOTAL_CHARS + " chars)...</div>";
        },
        onLinks: function(count) {
            aiContainer.innerHTML = "<div style='padding: 10px; font-weight: bold; color: " + modeColor + ";'>" + modeText + " Enterprise: Fetching " + count + " linked Jira issues...</div>";
        }
    })
    .then(ctx => {
        var injectedContext = ctx.context;
        var systemPrompt = ctx.systemPrompt;

        // DYNAMIC ROUTING BASED ON MODE
        if (isEnterprise) {
            console.log("=== ENTERPRISE DATA SENT ===");
            console.log("TOTAL PAYLOAD LENGTH:", injectedContext.length);

            var blobLinks = aiGetFailBlobLinks(ctx.logDomNode);
            console.log("🔍 Found " + blobLinks.length + " attachment link(s) in failing rows.");

            return Promise.all(blobLinks.slice(0, 2).map(url => fetch(url).then(r => r.blob()).catch(e => null)))
            .then(blobs => {
                var payload = [];
                var extraText = '';
                var imageCount = 0;

                return Promise.all(blobs.filter(b => b).map(blob => new Promise(res => {
                    var reader = new FileReader();
                    reader.onloadend = () => {
                        if (blob.type.indexOf('image') !== -1) {
                            payload.push({ type: 'image_url', image_url: { url: reader.result } });
                            imageCount++;
                        } else {
                            extraText += '\n\n--- ATTACHED FILE (' + blob.type + ') ---\n' + reader.result;
                        }
                        res();
                    };
                    blob.type.indexOf('image') !== -1 ? reader.readAsDataURL(blob) : reader.readAsText(blob);
                }))).then(() => {
                    injectedContext += extraText;

                    var finalPayload;
                    if (imageCount > 0) {
                        console.log('📸 Successfully encoded ' + imageCount + ' image(s) for Gemini Vision!');
                        finalPayload = [{ type: 'text', text: injectedContext }].concat(payload);
                    } else {
                        finalPayload = injectedContext;
                    }

                    console.log('=== FINAL PAYLOAD GOING TO PROXY ===', finalPayload);

                    return fetch('copilot_proxy.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            apiKey: apiKey,
                            model: selectedModel,
                            messages: [
                                { role: 'system', content: systemPrompt },
                                { role: 'user', content: finalPayload }
                            ]
                        })
                    });
                });
            });
        } else {
            console.log("=== FREE TIER DATA SENT ===");
            console.log("TOTAL PAYLOAD LENGTH:", injectedContext.length);
            return fetch('https://models.inference.ai.azure.com/chat/completions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + apiKey
                },
                body: JSON.stringify({
                    model: selectedModel,
                    messages: [
                        { role: 'system', content: systemPrompt },
                        { role: 'user', content: injectedContext }
                    ],
                    temperature: 0.1
                })
            });
        }
    })
    .then(aiResponse => {
        if (!aiResponse.ok) {
            if (aiResponse.status === 413 && !isEnterprise) {
                throw new Error('API Error 413: Content Too Large. The logs exceed the max size allowed by the free API tier.');
            }
            if (aiResponse.status === 401 || aiResponse.status === 403) {
                localStorage.removeItem('user_llm_api_key');
                throw new Error(isEnterprise ? 'Enterprise Token rejected. Make sure your Vim token is still valid!' : 'GitHub Token rejected. Please enter a valid PAT.');
            }
            throw new Error('API Error: ' + aiResponse.statusText);
        }
        return aiResponse.json();
    })
    .then(aiData => {
        var analysisText = aiData.choices[0].message.content;

        var formattedHtml = analysisText.replace(/</g, '&lt;').replace(/>/g, '&gt;');
        formattedHtml = formattedHtml.replace(/```[a-zA-Z]*\n([\s\S]*?)```/gi, '<div style="background:#2b2b2b; color:#f8f8f2; padding:12px; border-radius:6px; font-family:monospace; white-space:pre-wrap; margin:10px 0; overflow-x:auto;">$1</div>');
        formattedHtml = formattedHtml.replace(/`([^`]+)`/g, '<span style="background:#e0e0e0; color:#c7254e; padding:2px 5px; border-radius:3px; font-family:monospace; font-size:12px;">$1</span>');
        formattedHtml = formattedHtml.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

        formattedHtml = formattedHtml.replace(/^###### (.*$)/gim, '<h6 style="margin-top:15px; margin-bottom:5px; color:#222;">$1</h6>');
        formattedHtml = formattedHtml.replace(/^##### (.*$)/gim, '<h5 style="margin-top:15px; margin-bottom:5px; color:#222;">$1</h5>');
        formattedHtml = formattedHtml.replace(/^#### (.*$)/gim, '<h4 style="margin-top:15px; margin-bottom:5px; color:#222;">$1</h4>');
        formattedHtml = formattedHtml.replace(/^### (.*$)/gim, '<h3 style="margin-top:15px; margin-bottom:5px; color:#222;">$1</h3>');
        formattedHtml = formattedHtml.replace(/^## (.*$)/gim, '<h2 style="margin-top:15px; margin-bottom:5px; color:#222;">$1</h2>');
        formattedHtml = formattedHtml.replace(/^# (.*$)/gim, '<h1 style="margin-top:15px; margin-bottom:5px; color:#222;">$1</h1>');

        formattedHtml = formattedHtml.replace(/^[-*] (.*$)/gim, '<li style="margin-left:20px; margin-bottom:3px;">$1</li>');

        var safeMarkdown = encodeURIComponent(analysisText).replace(/'/g, "\%27");

        aiContainer.innerHTML = "<div style='background: " + modeBg + "; padding: 20px; border-radius: 8px; border: 1px solid #d1d5da; margin: 15px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.05);'>" +
            "<h4 style='margin-top:0; margin-bottom:15px; border-bottom:1px solid #e1e4e8; padding-bottom:10px; color:#24292e; font-size:16px;'>" + modeText + " AI Root Cause Analysis</h4>" +
            "<div style='white-space: pre-wrap; font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #24292e;'>" + formattedHtml + "</div>" +
            "<div style='margin-top: 20px; display: flex; gap: 10px;'>" +
                "<button style='padding: 6px 12px; cursor: pointer; border: 1px solid #d1d5da; background: #fafbfc; border-radius: 6px; font-weight:bold; color:#24292e;' onclick='fallbackCopyTextToClipboard(\"" + safeMarkdown + "\")'>📋 Copy Markdown</button>" +
                "<button style='padding: 6px 12px; cursor: pointer; border: 1px solid #d1d5da; background: #fafbfc; border-radius: 6px; font-weight:bold; color:#cb2431;' onclick='localStorage.removeItem(\"user_llm_api_key\"); alert(\"Token cleared!\");'>Clear Saved AI Token</button>" +
            "</div>" +
        "</div>";
    })
    .catch(error => {
        if (aiContainer) {
            aiContainer.innerHTML = "<div style='color: #cb2431; background-color: #ffeef0; padding: 15px; border: 1px solid #f97583; border-radius: 6px; margin: 15px 0;'><b>Error:</b> " + error.message + "</div>";
        }
    });
}

// =======================================================================
// CUSTOM AI (only builds context, user copies it to any AI tool)
// =======================================================================

// texts are kept here, not in onclick attributes (they are big)
var aiContextTexts = {};

function aiEscapeHtml(text) {
    return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function aiCustomContext(testid) {
    var info = aiGetTestInfo(testid);
    if (!aiIsTestLoaded(info)) return;

    var aiContainer = aiGetContainer(info);
    var headerStyle = "padding: 10px; font-weight: bold; color: #444;";
    aiContainer.innerHTML = "<div style='" + headerStyle + "'>Custom AI: collecting context for " + aiEscapeHtml(info.testMethodName) + "...</div>";

    aiBuildContext(info, {
        maxTotalChars: AI_CONFIG.custom.maxTotalChars,
        maxLineChars: AI_CONFIG.custom.maxLineChars,
        withLinkedIssues: true,
        onLinks: function(count) {
            aiContainer.innerHTML = "<div style='" + headerStyle + "'>Custom AI: fetching " + count + " linked Jira issues...</div>";
        }
    })
    .then(ctx => {
        var fullText = "<instructions>\n" + ctx.systemPrompt + "\n</instructions>\n\n" + ctx.context;
        aiContextTexts[testid] = fullText;

        var screenshots = aiGetFailBlobLinks(ctx.logDomNode).length;
        var buttonStyle = "padding: 6px 12px; cursor: pointer; border: 1px solid #d1d5da; background: #fafbfc; border-radius: 6px; font-weight:bold; color:#24292e;";

        aiContainer.innerHTML = "<div style='background: #f9f9fc; padding: 20px; border-radius: 8px; border: 1px solid #d1d5da; margin: 15px 0;'>" +
            "<h4 style='margin-top:0; margin-bottom:10px; color:#24292e; font-size:16px;'>Custom AI: context for " + aiEscapeHtml(info.testMethodName) + "</h4>" +
            "<div style='font-size: 13px; color: #444; line-height: 1.6;'>" +
                fullText.length + " chars (~" + Math.round(fullText.length / 4) + " tokens), " +
                "source code: " + (ctx.hasSourceCode ? "included" : "not found") +
                (screenshots > 0 ? ", screenshots in failing rows: " + screenshots + " (not included)" : "") +
            "</div>" +
            "<div style='margin-top: 15px;'>" +
                "<button style='" + buttonStyle + "' onclick='aiCopyText(aiContextTexts[" + testid + "], \"Context\")'>📋 Copy context</button>" +
            "</div>" +
            "<details style='margin-top: 10px;'><summary style='cursor:pointer; font-size: 13px;'>Show context</summary>" +
                "<pre style='white-space: pre-wrap; font-size: 12px; max-height: 400px; overflow: auto; background: #fff; border: 1px solid #e1e4e8; padding: 10px;'>" + aiEscapeHtml(fullText) + "</pre>" +
            "</details>" +
        "</div>";
    })
    .catch(error => {
        aiContainer.innerHTML = "<div style='color: #cb2431; background-color: #ffeef0; padding: 15px; border: 1px solid #f97583; border-radius: 6px; margin: 15px 0;'><b>Error:</b> " + aiEscapeHtml(error.message) + "</div>";
    });
}

// copy any text, textarea fallback for http pages (no navigator.clipboard there)
function aiCopyText(text, label) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(function() {
            alert(label + " copied to clipboard!");
        }, function(err) {
            console.error("Could not copy text: ", err);
        });
        return;
    }
    var textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    textArea.style.opacity = "0";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        if (document.execCommand('copy')) {
            alert(label + " copied to clipboard!");
        } else {
            console.error("Fallback copy failed.");
        }
    } catch (err) {
        console.error("Fallback copy errored: ", err);
    }
    document.body.removeChild(textArea);
}


function fallbackCopyTextToClipboard(encodedText) {
    var rawText = decodeURIComponent(encodedText);
    
    // Check if HTTPS/Localhost is available
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(rawText).then(function() {
            alert("Markdown copied to clipboard!");
        }, function(err) {
            console.error("Could not copy text: ", err);
        });
        return;
    }
    
    // HTTP Fallback
    var textArea = document.createElement("textarea");
    textArea.value = rawText;
    
    // Hide the textarea from view
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    textArea.style.opacity = "0";

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        var successful = document.execCommand('copy');
        if (successful) {
            alert("Markdown copied to clipboard!");
        } else {
            console.error("Fallback copy failed.");
        }
    } catch (err) {
        console.error("Fallback copy errored: ", err);
    }
    
    document.body.removeChild(textArea);
}
