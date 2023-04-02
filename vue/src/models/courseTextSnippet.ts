export interface CourseTextSnippet {
  time: number
  timeEnd: number
  type: CourseTextSnippetType
  value: string
}

export enum CourseTextSnippetType {
    sentence = 'sentence',
    chapter = 'chapter',
    section = 'section',
    subsection = 'subsection'
}