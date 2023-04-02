const fs = require("fs");

//TODO Change this to be dynamic
const courseData = require("../marks/ke6-original.json");

// tag regex (source: https://www.data2type.de/xml-xslt-xslfo/regulaere-ausdruecke/regex-methoden-aus-der-praxis/beispiele-zu-html/html-tags-erkennen)
const tagRegex = /<[^>]+>/g;

// for each textblock entry

//TODO Change this to be dynamic
// This will brake the audiocues, so some maintenance has to be done there as well.
const tmpFile = fs.createWriteStream("../marks/ke6-cleaned.json");
const cleanedTextBlocks = [];

tmpFile.on("open", (fd) => {
    for (const textBlock of courseData) {
        const { type, value } = textBlock;

        // remove words, since we only use sentences (reduce filesize)
        if (type === "word") continue;

        if (type === "ssml") {
            if (value === "item-0" || value === "item-1") {
                // This was an item, that played around with the resonance audio system. Doesn't bring any value for us.
                continue;
            } else if (value.includes("-")) {
                const [extractedType, ...splittedValue] = value.split("-");

                // Right now we only need these types in our ke.json
                if (
                    extractedType === "chapter" ||
                    extractedType === "section" ||
                    extractedType === "subsection" ||
                    extractedType === "sentence" ||
                    extractedType === 'url'
                ) {
                    textBlock.type = extractedType

                    // There is a Problem in which sections look like this: section-Awareness-Entwurfsmuster. In this case, the array will have > 1 elements.
                    // This is a problem based on the source data we get. The type separator is the same as part of the chapter itself.
                    textBlock.value = splittedValue.length > 1 ? splittedValue.join('-') : splittedValue.join('');
                } else if (extractedType === 'image') {
                    const [ _, imageFile ] = splittedValue[0].split('fig/')
                    textBlock.type = extractedType
                    textBlock.value = imageFile
                } else {
                    continue;
                }

                // I want to leave this here, in case we need to deal with other types.
                // handle special case where pattern-desc- exists.
                // if (extractedType === 'pattern' && splittedValue[0] === 'desc') {
                //     textBlock.type = extractedType + 'description'
                //     splittedValue.shift()
                //     textBlock.value = splittedValue.join('')
                // } else if (splittedValue.length > 1) {
                // handle special case for pattern-Remote-Field-of-Vision
                //     textBlock.type = extractedType
                //     textBlock.value = splittedValue.join(' ')
                // } else {
                // Make e.g. 'subsection-Awareness', 'subsection' the real 'type' attribute.
                //     textBlock.type = extractedType
                //     textBlock.value = splittedValue.join('')
                // }
            } else {
                // removes all 'ssml' types which are not written with a - in between.
                // WARNING: This will also remove 'listing' types, so when listings are supported in the future, this should be changed.
                // Removed types: discussion, figurecaption, listing, example, footnote
                continue;
            }
        }

        // Cleanup 'value' from all tag elements in the value attribute.
        textBlock.value = textBlock.value.replace(tagRegex, "");

        // Push the cleaned up textblock into a new array.
        cleanedTextBlocks.push(textBlock);

        // Start and end character index is not used, reduce filesize.
        delete textBlock.start;
        delete textBlock.end;
    }

    for (const [
        index,
        currentCleanedTextBlock,
    ] of cleanedTextBlocks.entries()) {
        // Set endTime and time attributes for each sentence block
        let currentIndex = index
        if (currentCleanedTextBlock.type === 'sentence') currentIndex = findNextSentenceIndex(cleanedTextBlocks, index)

        currentCleanedTextBlock.timeEnd = cleanedTextBlocks[currentIndex]
            ? Math.floor(cleanedTextBlocks[currentIndex].time / 1000)
            : Math.floor((currentCleanedTextBlock.time + 10000) / 1000);

        currentCleanedTextBlock.time = Math.floor(currentCleanedTextBlock.time / 1000)
    }

    // Finally, write the changes to the new json file.
    tmpFile.write(JSON.stringify(cleanedTextBlocks));
});

const findNextSentenceIndex = (cleanedTextBlocks, startindex) => {
    for (let index = startindex + 1; index < cleanedTextBlocks.length; index++) {
        const element = cleanedTextBlocks[index];
        if (element.type !== 'sentence') continue;

        return index;
    }
}
