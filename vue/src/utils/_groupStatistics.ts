import store from '@/store';
import { Group } from '@/models';
import Communication from '@/scripts/communication';
import { LogEntry } from '@/models/logEntry';

const approxCanvasWidth = 400;
const canvasHeigth = 10;
// Max Opaqueness at peak usage (each set following buildLayout)
let metaImpressionBoundary = 0;
let commentBoundary = 0;
let questionBoundary = 0;
let VoiceChatBoundary = 0;
const color = 'rgb(40,167,69)';
let duration = 0;

let occuredUser: number[];

interface eventData {
    userid: number;
    timestamp: number;
    LiveSession: boolean;
    timecreated: number;
}
export interface commentData {
    groupid: number;
    timestamp: number;
    deleted: number;
    category: string;
}

export async function statComments(
    target: HTMLCanvasElement,
    comments: commentData[],
    category: string | null
) {
    //check or set the Duration of audio
    await retrieveFileDuration();

    // Filter Comments if needed
    if (category != null)
        comments = comments.filter((element) => element.category === category);
    // evaluate occurance of comments
    let layout = buildCommentLayout(comments);
    // evaluate max. usage
    if (category != null) {
        questionBoundary = maxOfArray(layout);
    } else {
        commentBoundary = maxOfArray(layout);
    }
    // Build Canvas following the usage
    buildCanvas(
        target,
        layout,
        category != null ? questionBoundary : commentBoundary
    );
}
export async function statVoicechat(
    target: HTMLCanvasElement,
    log: LogEntry[],
    group: Group | null
) {
    //check or set the Duration of audio
    await retrieveFileDuration();
    if (group) {
        log = log.filter((element) => {
            return element.groupid === group.id;
        });
    }

    let layout = buildVoicechatLayout(log, VoiceChatBoundary);

    // evaluate max. usage
    VoiceChatBoundary = maxOfArray(layout);

    // Build Canvas following the ratio
    buildCanvas(target, layout, VoiceChatBoundary);
}

export function buildTable(
    comments: commentData[],
    log: LogEntry[]
): [string, number][] {
    let tableItems: [string, number][] = [
        [
            'Gegründete Gruppen',
            log.filter((logEntry) => {
                return logEntry.event === 'create_group';
            }).length,
        ],
    ];

    tableItems.push([
        'Gelöschte Gruppen',
        log.filter((logEntry) => {
            return logEntry.event === 'delete_group';
        }).length,
    ]);

    tableItems.push(['Kommentare gesamt', comments.length]);
    tableItems.push(tableItemComment('Frage', comments));
    tableItems.push(tableItemComment('Zusammenfassung', comments));
    tableItems.push(tableItemComment('Diskussion', comments));
    tableItems.push(tableItemComment('Zusatzinformation', comments));
    tableItems.push(tableItemComment('Markierung', comments));
    return tableItems;
}

export async function getNotUser(): Promise<string> {
    let enrolledUser = await Communication.webservice('getEnrolledUser', {
        cmid: `${store.getters.getCourseModuleID}`,
    });
    let res = 0;
    enrolledUser.forEach((element) => {
        if (!occuredUser.find((elem) => elem === element.id)) res++;
    });
    let temp = (res * 100) / enrolledUser.length;
    return res + ' (' + temp.toFixed(2) + '%)';
}

function tableItemComment(
    category: string,
    comments: commentData[]
): [string, number] {
    return [
        category,
        comments.filter((comment) => {
            return comment.category === category;
        }).length,
    ];
}

export async function statProgressListened(
    target: HTMLCanvasElement,
    group: Group | null
) {
    //check or set the Duration of audio
    await retrieveFileDuration();
    // Read log and evaluate Usage of Hypercast
    let layout = await retrieveDataPlaytime(group);

    // Build Canvas following the usage
    buildCanvas(target, layout, metaImpressionBoundary);
}

export async function retrieveFileDuration(): Promise<number> {
    if (duration > 0) return duration;
    //TODO Update if Location is not hardcoded: groupID->courseID->fileLocatiom
    duration = await fetchDuration(
        `${store.getters.getPluginBaseURL}/assets/hyperaudio/audio/courses/ke6.mp3`
    );
    return duration;
}

function fetchDuration(path): Promise<number> {
    return new Promise((resolve) => {
        const audio = new Audio();
        audio.src = path;
        audio.addEventListener('loadedmetadata', () => {
            resolve(audio.duration);
        });
    });
}

function maxOfArray(array: number[]): number {
    let result = array.reduce(function (prev, current) {
        return prev > current ? prev : current;
    });
    //prevent  x/0
    return result === 0 ? 1 : result;
}

function buildVoicechatLayout(log: LogEntry[], boundary: number): number[] {
    let result = new Array<number>(Math.floor(duration / 30) + 1);
    // Arrays to count events in 30s-Chunks
    let unmutesConsolidated = new Array<number>(Math.floor(duration / 30) + 1);
    let pauseConsolidated = new Array<number>(Math.floor(duration / 30) + 1);
    //Initialization
    for (let i = 0; i < result.length; i++) {
        unmutesConsolidated[i] = 0;
        pauseConsolidated[i] = 0;
        result[i] = 0;
    }

    log.forEach((elem) => {
        if (elem.event === 'ls_paused')
            pauseConsolidated[Math.floor(<number>(<any>elem.data) / 30)]++;
        if (elem.event === 'vc_unmute')
            unmutesConsolidated[Math.floor(<number>(<any>elem.data) / 30)]++;
    });

    // Compute average voicechats per pause
    let res = 0;
    for (let i = 0; i < result.length; i++) {
        if (pauseConsolidated[i] === 0) {
            res = unmutesConsolidated[i];
        } else {
            res = unmutesConsolidated[i] / pauseConsolidated[i];
        }
        if (res) result[i] = res;
    }
    return result;
}

function buildCommentLayout(comments: commentData[]): number[] {
    // Arrays to count comments in 30s-Chunks
    let commentsConsolidatet = new Array<number>(Math.floor(duration / 30) + 1);
    //Initialization
    for (let i = 0; i < commentsConsolidatet.length; i++) {
        commentsConsolidatet[i] = 0;
    }
    comments.forEach((elem) => {
        commentsConsolidatet[Math.floor(elem.timestamp / 30)]++;
    });
    return commentsConsolidatet;
}

// Build the canvas from usage stored in layout put in relation to boundary
// items of layout have to be limited to boundary

function buildCanvas(
    target: HTMLCanvasElement,
    layout: number[],
    boundary: number
): void {
    const slotSize = Math.floor(approxCanvasWidth / layout.length);

    let canvas: HTMLCanvasElement = target;
    canvas.setAttribute('width', <string>(<any>(layout.length * slotSize + 2)));
    canvas.setAttribute('height', <string>(<any>(canvasHeigth + 2)));
    if (canvas.getContext) {
        const ctx = canvas.getContext('2d')!;
        ctx.strokeRect(0, 0, layout.length * slotSize + 1, canvasHeigth + 1);
        for (let i = 0; i < layout.length; i++) {
            ctx.fillStyle = color;
            ctx.globalAlpha = layout[i] / boundary;
            ctx.fillRect(i * slotSize + 1, 1, slotSize, canvasHeigth);
        }
    }
}

async function retrieveDataPlaytime(group: Group | null): Promise<number[]> {
    let events: eventData[];
    let eventList;
    events = [
        {
            userid: 0,
            LiveSession: true,
            timestamp: 0,
            timecreated: 0,
        },
    ];
    try {
        if (group != null) {
            eventList = await Communication.webservice('getGroupProgress', {
                groupid: group.id,
            });
        } else {
            eventList = await Communication.webservice('getGroupProgress', {
                groupid: -1,
            });
        }
        occuredUser = [0];
        if (eventList) {
            // convert to an usable format
            eventList.forEach((element) => {
                events.push({
                    userid: element.userid,
                    LiveSession: JSON.parse(element.data).live,
                    timestamp: JSON.parse(element.data).progress,
                    timecreated: element.timecreated,
                });
                // Store users witht progress for later use
                if (!occuredUser.find((elem) => elem === element.userid))
                    occuredUser.push(element.userid);
            });

            events = events.slice(1);

            // there are entries for each participant of a LiveSession, one should be enough
            events = events.filter(
                (value, index, self) =>
                    index ===
                    self.findIndex(
                        (t) =>
                            t.LiveSession === value.LiveSession &&
                            t.timecreated === value.timecreated &&
                            t.LiveSession
                    )
            );
        }
    } catch (error) {
        console.log(error);
    }

    // Arrays to count listeners/LiveSession in 30s-Chunks
    let liveSessions = new Array<number>(Math.floor(duration / 30) + 1);
    let userSessions = new Array<number>(Math.floor(duration / 30) + 1);
    // metaImpressionarency (=1/opaque) for the Canvas
    let opaqueness = new Array<number>(Math.floor(duration / 30) + 1);

    //Initialization
    for (let i = 0; i < liveSessions.length; i++) {
        liveSessions[i] = 0;
        userSessions[i] = 0;
        opaqueness[i] = 0;
    }
    events.forEach((elem) => {
        if (elem.LiveSession) {
            liveSessions[Math.floor(elem.timestamp / 30)]++;
        } else {
            userSessions[Math.floor(elem.timestamp / 30)]++;
        }
    });

    // Apply metaImpression to Trasparancy: Counter of at least 8 (=80%) is one impression, LiveSession or 50 % groupmembers alone is one MetaImpression
    // Opacity = MetaImpression / metaImpressionBoundary  (but <= 1: max(Inmpression, Boundary) is returned)
    let metaImpression = 0;
    for (let i = 0; i < opaqueness.length; i++) {
        metaImpression = 0;
        metaImpression = Math.floor(liveSessions[i] / 8);
        if (group) {
            metaImpression =
                metaImpression +
                Math.floor(userSessions[i] / (8 * group.members.length * 0.5));
        }
        opaqueness[i] = metaImpression;
    }
    metaImpressionBoundary = maxOfArray(opaqueness);

    return opaqueness;
}

export async function retrieveAllComments(
    group: Group | null
): Promise<commentData[]> {
    // Filter is only hypercastID and !referenceID (= is no reply) and is set in Backend
    let comments: commentData[];
    comments = [
        {
            groupid: 0,
            timestamp: 0,
            deleted: 0,
            category: '',
        },
    ];
    try {
        if (group != null) {
            comments = await Communication.webservice('getAllComments', {
                cmid: `${store.getters.getCourseModuleID}`,
                groupid: group.id,
            });
        } else {
            comments = await Communication.webservice('getAllComments', {
                cmid: `${store.getters.getCourseModuleID}`,
                groupid: -1,
            });
        }
    } catch (error) {
        console.log(error);
    }
    comments.slice(1);
    return comments;
}
